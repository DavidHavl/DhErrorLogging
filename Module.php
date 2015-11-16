<?php

namespace DhErrorLogging;

use Zend\Console\Console;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Application;
use Zend\Mvc\ResponseSender\SendResponseEvent;
use Zend\EventManager\EventInterface;
use Zend\Log\Logger;
use Zend\Db\Adapter;
use Zend\View\Model\ModelInterface;
use Zend\ModuleManager\Feature;

class Module implements
    Feature\BootstrapListenerInterface,
    Feature\AutoloaderProviderInterface,
    Feature\ConfigProviderInterface
{

    private $logger;
    private $generator;
    private $exceptionFilter;
    private $options;
    private $nonMvcResponseSender;

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }


    public function onBootstrap(EventInterface $e)
    {

        $app = $e->getApplication();
        $eventManager = $app->getEventManager()->getSharedManager();
        $serviceManager = $app->getServiceManager();
        $this->options = $options = $serviceManager->get('DhErrorLogging\Options\ModuleOptions');

        // return if it is not enabled
        if (!$options->isEnabled()) {
            return;
        }

        // get logger
        $this->logger = $serviceManager->get('DhErrorLogging\Logger');
        $this->generator = $serviceManager->get('DhErrorLogging\Generator\ErrorReferenceGenerator');
        $this->exceptionFilter = $serviceManager->get('DhErrorLogging\Filter\ExceptionFilter');
        $this->nonMvcResponseSender = $serviceManager->get('DhErrorLogging\Sender\ResponseSender');

        // Handle native PHP errors
        if ($options->isErrortypeEnabled('errors')) {
            $this->attachErrorHandler();
        }

        // Handle those exceptions that do not get caught by MVC
        if ($options->isErrortypeEnabled('exceptions')) {
            $this->attachExceptionHandler();
        }

        // Handle framework specific errors
        if ($options->isErrortypeEnabled('dispatch')) {
            $eventManager->attach('Zend\Mvc\Application', MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'attachDispatchErrorHandler'));
        }
        if ($options->isErrortypeEnabled('render')) {
            $eventManager->attach('Zend\Mvc\Application', MvcEvent::EVENT_RENDER_ERROR, array($this, 'attachRenderErrorHandler'));
        }

        // Handle fatal errors
        if ($options->isErrortypeEnabled('fatal')) {
            $this->attachFatalErrorHandler();
        }
    }



    public function attachErrorHandler()
    {
        // Handle native PHP errors
        set_error_handler(function ($level, $message, $file, $line) {
            $minErrorLevel = error_reporting();
            if ($minErrorLevel & $level) {
                $errorReference = $this->generator->generate();
                $extra = array(
                    'type'  => 'ERROR',
                    'file'  => $file,
                    'line'  => $line,
                    'reference' => $errorReference
                );
                // translate error type to log type.
                $logType = Logger::ERR;
                if (isset(Logger::$errorPriorityMap[$level])) {
                    $logType = Logger::$errorPriorityMap[$level];
                }

                // log it
                $this->logger->log($logType, $message, $extra);

                if ($this->options->getDisplayableErrorLevels() & $level) {
                    $this->nonMvcResponse($extra, $message);
                    // return false to not continue native handler
                    return false;
                }
                return true;
            }
        });
    }


    public function attachExceptionHandler()
    {
        set_exception_handler(function ($exception) {
            $logs = array();

            do {
                $priority = Logger::ERR;

                if ($exception instanceof \ErrorException && isset(Logger::$errorPriorityMap[$exception->getSeverity()])) {
                    $priority = Logger::$errorPriorityMap[$exception->getSeverity()];
                }

                // log only desired exceptions
                if (!empty($exception) && !$this->exceptionFilter->isAllowed($exception)) {
                    return;
                }


                $errorReference = $this->generator->generate();
                $extra = array(
                    'type'  => 'EXCEPTION',
                    'file'  => $exception->getFile(),
                    'line'  => $exception->getLine(),
                    'trace' => $exception->getTrace(),
                    'reference' => $errorReference
                );
                if (isset($exception->xdebug_message)) {
                    $extra['xdebug'] = $exception->xdebug_message;
                }

                $logs[] = array(
                    'priority' => $priority,
                    'message'  => $exception->getMessage(),
                    'extra'    => $extra,
                );
                $exception = $exception->getPrevious();
            } while ($exception);


            foreach (array_reverse($logs) as $log) {
                $this->logger->log($log['priority'], $log['message'], $log['extra']);
            }

            $params = $logs[0]['extra'];
            $params['message'] = $logs[0]['message'];

            $this->nonMvcResponse($params);
            // return false to not continue other handlers
            return false;
        });
    }


    public function attachDispatchErrorHandler($event)
    {

        // check if event is error
        if (!$event->isError()) {
            return;
        }

        $errorType = E_ERROR;
        // get message and exception (if present)
        $message = $event->getError();
        $exception = $event->getParam('exception');

        $type = 'DISPATCH';

        // 404 route not found exception
        if ($message == Application::ERROR_ROUTER_NO_MATCH) {
            if (empty($this->config['dherrorlogging']['error_types']['dispatch\router_no_match'])) {
                return;
            }
            $type = '404';
        }

        // exception filter
        if (!empty($exception) && !$this->exceptionFilter->isAllowed($exception)) {
            return;
        }

        // generate unique reference for this error
        $errorReference = $this->generator->generate();
        $extra = array(
            'reference' => $errorReference,
            'type'  => $type
        );
        // check if event has exception and populate extras array.
        if (!empty($exception)) {
            $message = $exception->getMessage();
            $extra['file'] = $exception->getFile();
            $extra['line'] = $exception->getLine();
            $extra['trace'] = $exception->getTrace();

            // check if xdebug is enabled and message present in which case add it to the extra
            if (isset($exception->xdebug_message)) {
                $extra['xdebug'] = $exception->xdebug_message;
            }

            if (method_exists($exception, 'getSeverity')) {
                $errorType = $exception->getSeverity();
            }
        }

        // translate error type to log type.
        $logType = Logger::ERR;
        if (isset(Logger::$errorPriorityMap[$errorType])) {
            $logType = Logger::$errorPriorityMap[$errorType];
        }

        // log it
        $this->logger->log($logType, $message, $extra);

        $this->mvcResponse('dispatch', $event, $extra);
    }

    public function attachRenderErrorHandler($event)
    {
        // check if event is an error
        if (!$event->isError()) {
            return;
        }

        $errorType = E_ERROR;
        // get message and exception (if present)
        $message = $event->getError();
        $exception = $event->getParam('exception');

        $type = 'RENDER';

        // exception filter
        if (!empty($exception) && !$this->exceptionFilter->isAllowed($exception)) {
            return;
        }

        // generate unique reference for this error
        $errorReference = $this->generator->generate();
        $extra = array(
            'reference' => $errorReference,
            'type'  => $type
        );
        // check if event has exception and populate extras array.
        if (!empty($exception)) {
            $message = $exception->getMessage();
            $extra['file'] = $exception->getFile();
            $extra['line'] = $exception->getLine();
            $extra['trace'] = $exception->getTrace();

            // check if xdebug is enabled and message present in which case add it to the extra
            if (isset($exception->xdebug_message)) {
                $extra['xdebug'] = $exception->xdebug_message;
            }

            if (method_exists($exception, 'getSeverity')) {
                $errorType = $exception->getSeverity();
            }
        }

        // translate error type to log type.
        $logType = Logger::ERR;
        if (isset(Logger::$errorPriorityMap[$errorType])) {
            $logType = Logger::$errorPriorityMap[$errorType];
        }

        // log it
        $this->logger->log($logType, $message, $extra);

        $this->mvcResponse('render', $event, $extra);
    }

    public function attachFatalErrorHandler()
    {
        // catch also fatal errors which would not show the regular error template.
        register_shutdown_function(function () {
            $error = error_get_last();
            // check we have valid error object
            if (null === $error || !isset($error['type'])) {
                return;
            }
            // allow only catchable errors
            if ($error['type'] !== E_ERROR && $error['type'] !== E_PARSE && $error['type'] !== E_RECOVERABLE_ERROR) {
                return;
            }

            $errorReference = $this->generator->generate();

            $extra = array(
                'type'  => 'FATAL',
                'reference' => $errorReference,
                'file'      => $error['file'],
                'line'      => $error['line']
            );

            // translate error type to log type.
            $logType = Logger::ERR;
            if (isset(Logger::$errorPriorityMap[$error['type']])) {
                $logType = Logger::$errorPriorityMap[$error['type']];
            }

            try {
                // log error using logger
                $this->logger->log($logType, $error['message'], $extra);
            }
            catch (\Exception $e) {
                // if fatal error occured it could be a log writer problem
                // so write it to PHP log file nad show a nice error message
                error_log(sprintf('[Error reference: %s] %s', $errorReference, $error['message']), 0);
            }

            $this->nonMvcResponse($extra, $error['message']);
       });
    }

    private function nonMvcResponse($extra, $message = '') {
        $responseEvent = new SendResponseEvent();
        if (!empty($message)) {
            $extra = array_merge($extra, array('message' => $message));
        }
        $responseEvent->setParams($extra);
        $this->nonMvcResponseSender->send($responseEvent);
    }

    private function mvcResponse($name, $event, $extra = []) {
        // hijack error view and add error reference variable to the view
        $viewModel = $event->getResult();
        if ($viewModel instanceof ModelInterface) {
            // if template specified, use it
            if ($this->options->getTemplate($name)) {
                $mapresolver = $event->getApplication()->getServiceManager()->get('ViewTemplateMapResolver');
                if (!$mapresolver->has('dherrorlogging/'.$name)) { // if not specified, assign it from config
                    $path = $this->options->getTemplate($name);
                    $mapresolver->add('dherrorlogging/'.$name, realpath($path));
                }
                $viewModel->setTemplate('dherrorlogging/'.$name);
            }
            if (isset($extra['reference'])) {
                $viewModel->setVariable('errorReference', $extra['reference']);
            }
        }
    }
}
