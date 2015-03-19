<?php

namespace DhErrorLogging;

use Zend\Mvc\MvcEvent;
use Zend\Mvc\Application;
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
    private $ptions;

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


        // Handle native PHP errors
        if ($options->isErrortypeEnabled('native')) {
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
                $extra = array(
                    'type'  => 'ERROR',
                    'file'  => $file,
                    'line'  => $line,
                    'reference' => $this->generator->generate()
                );
                // translate error type to log type.
                $logType = Logger::ERR;
                if (isset(Logger::$errorPriorityMap[$level])) {
                    $logType = Logger::$errorPriorityMap[$level];
                }

                // log it
                $this->logger->log($logType, $message, $extra);
echo "IN ERROR. DH!!";
var_dump($message);die();// TODO: remove me
            }
            // return false to not continue native handler
            return false;
        });

    }


    public function attachExceptionHandler()
    {
        set_exception_handler(function ($exception) {
            $logs = array();

            do {
                $priority = Logger::ERR;
                // TODO: implement $exceptionFilter instead of the bellow?
                if ($exception instanceof \ErrorException && isset(Logger::$errorPriorityMap[$exception->getSeverity()])) {
                    $priority = Logger::$errorPriorityMap[$exception->getSeverity()];
                }

                $extra = array(
                    'type'  => 'EXCEPTION',
                    'file'  => $exception->getFile(),
                    'line'  => $exception->getLine(),
                    'trace' => $exception->getTrace(),
                    'reference' => $this->generator->generate()
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
echo "IN EXCEPTION. DH!!";
var_dump($logs);die(); // TODO: remove me


            // TODO: do we need to show the error view template? Via ViewModel?

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

        // TODO: test if when we disable both the error handler still shows in the nice template.

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
        $extras = array(
            'reference' => $errorReference,
            'type'  => $type
        );
        // check if event has exception and populate extras array.
        if (!empty($exception)) {
            $message = $exception->getMessage();
            $extras['file'] = $exception->getFile();
            $extras['line'] = $exception->getLine();
            $extras['trace'] = $exception->getTrace();

            // check if xdebug is enabled and message present in which case add it to the extras
            if (isset($exception->xdebug_message)) {
                $extras['xdebug'] = $exception->xdebug_message;
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
        $this->logger->log($logType, $message, $extras);

        // hijack error view and add error reference to the message
        $viewModel = $event->getResult();

        if ($viewModel instanceof ModelInterface) {
            $originalMessage = $viewModel->getVariable('message');
            $viewModel->setVariable('message', $originalMessage . '<br /> Error Reference: ' . $errorReference);
            // TODO: instead of appending to message just add new variable:    $event->getResult()->setVariable('errorReference', $errorReference);
            // TODO: in which case create a template with this variable or add instruction to README.md
            // TODO: check ExceptionStrategy class
            // TODO: but for console, have it still append because there is no view variable.
            // TODO: if ($viewModel instanceof ConsoleModel) {$viewModel->} else {set var}
        }

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
        $extras = array(
            'reference' => $errorReference,
            'type'  => $type
        );
        // check if event has exception and populate extras array.
        if (!empty($exception)) {
            $message = $exception->getMessage();
            $extras['file'] = $exception->getFile();
            $extras['line'] = $exception->getLine();
            $extras['trace'] = $exception->getTrace();

            // check if xdebug is enabled and message present in which case add it to the extras
            if (isset($exception->xdebug_message)) {
                $extras['xdebug'] = $exception->xdebug_message;
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
        $this->logger->log($logType, $message, $extras);

        // hijack error view and add error reference to the message
        $viewModel = $event->getResult();

        if ($viewModel instanceof ModelInterface) {
            $originalMessage = $viewModel->getVariable('message');
            $viewModel->setVariable('message', $originalMessage . '<br /> Error Reference: ' . $errorReference);
            // TODO: instead of appending to message just add new variable:    $event->getResult()->setVariable('errorReference', $errorReference);
            // TODO: in which case create a template with this variable or add instruction to README.md
            // TODO: check ExceptionStrategy class
            // TODO: but for console, have it still append because there is no view variable.
            // TODO: if ($viewModel instanceof ConsoleModel) {$viewModel->} else {set var}
        }

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

            // clean any previous output from buffer
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $errorReference = $this->generator->generate();

            $extras = array(
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
            // log error using logger
            $this->logger->log($logType, $error['message'], $extras);

            // get absolute path of the template to render (the shutdown method sometimes changes relative path).
            $fatalTemplatePath = dirname(__FILE__) . '/view/error/fatal.html';
            if (!empty($this->config['dherrorlogging']['templates']['fatal'])
                && file_exists($this->config['dherrorlogging']['templates']['fatal'])
            ) {

                $fatalTemplatePath = $this->config['dherrorlogging']['templates']['fatal'];
            }
            // read content of file
            $body = file_get_contents($fatalTemplatePath);
            // inject error reference
            $body = str_replace('%__ERROR_REFERENCE__%', 'Error Reference: ' . $errorReference, $body);
            echo $body;
            exit();
// TODO: first finish native / exceptions, then the accept for fatals...
            //$viewRender = $this->getServiceLocator()->get('ViewRenderer');
            //$html = $viewRender->render($viewModel);
        });
    }
}
