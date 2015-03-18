<?php

namespace DhErrorLogging;

use Zend\Mvc\MvcEvent;
use Zend\Log\Logger;
use Zend\Db\Adapter;
use Zend\View\Model;
use Zend\ModuleManager\Feature;

class Module implements
    Feature\BootstrapListenerInterface,
    Feature\AutoloaderProviderInterface,
    Feature\ConfigProviderInterface
{

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


    public function onBootstrap(MvcEvent $e)
    {

        $app = $e->getApplication();
        $serviceManager = $app->getServiceManager();
        $config = $serviceManager->get('config');

        // return if there is no config
        if (empty($config['dherrorlogging']['enabled'])) {
            return;
        }

        // get logger
        $logger = $serviceManager->get('DhErrorLogging\Logger');
        $generator = $serviceManager->get('DhErrorLogging\ErrorReferenceGenerator');

        // Handle native PHP errors
        if (!empty($config['dherrorlogging']['error_types']['native'])) {
            $this->setErrorHandler($logger, $generator);
        }

        // Handle those exceptions that do not get caught by MVC
        if (!empty($config['dherrorlogging']['error_types']['exceptions'])) {
            $this->setExceptionHandler($logger, $generator);
        }



        // Handle framework specific errors
        $eventTypes = array();
        if (!empty($config['dherrorlogging']['error_types']['dispatch'])) {
            $eventTypes[] = MvcEvent::EVENT_DISPATCH_ERROR;
        }
        if (!empty($config['dherrorlogging']['error_types']['render'])) {
            $eventTypes[] = MvcEvent::EVENT_RENDER_ERROR;
        }

        if (!empty($eventTypes)) {
            // Get event manager
            $eventManager = $app->getEventManager()->getSharedManager();
            // Handle framework specific errors
            $eventManager->attach('Zend\Mvc\Application', $eventTypes, function ($event) use ($logger, $generator) {

                // check if event is error
                if (!$event->isError()) {
                    return;
                }

                foreach ($event->getParams() as $key=>$val) {
                    echo $key;
                    echo '<br />';
                }

                $errorType = E_ERROR;
                // get message and exception (if present)
                $message = $event->getError();
                $exception = $event->getParam('exception');
                // generate unique reference for this error
                $errorReference = $generator->generate();
                $extras = array(
                    'reference' => $errorReference,
                    'type'  => 'MVC'
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
                $logger->log($logType, $message, $extras);

                // hijack error view and add error reference to the message
                $viewModel = $event->getResult();
                if ($viewModel instanceof ModelInterface) {
                    $originalMessage = $viewModel->getVariable('message');
                    $viewModel->setVariable('message', $originalMessage . '<br /> Error Reference: ' . $errorReference);
                }
            });
        }

        // Handle fatal errors
        if (!empty($config['dherrorlogging']['error_types']['fatal'])) {
            $this->setFatalErrorHandler($logger, $generator, $config);
        }
    }



    public function setErrorHandler($logger, $generator)
    {
        // Handle native PHP errors
        set_error_handler(function ($level, $message, $file, $line) use ($logger, $generator) {
            $minErrorLevel = error_reporting();
            if ($minErrorLevel & $level) {
                $extra = array(
                    'type'  => 'ERROR',
                    'file'  => $file,
                    'line'  => $line,
                    'reference' => $generator->generate()
                );
                // translate error type to log type.
                $logType = Logger::ERR;
                if (isset(Logger::$errorPriorityMap[$level])) {
                    $logType = Logger::$errorPriorityMap[$level];
                }

                // log it
                $logger->log($logType, $message, $extra);
            }
            // return false to not continue native handler
            return false;
        });

    }


    public function setExceptionHandler($logger, $generator)
    {
        set_exception_handler(function ($exception) use ($logger, $generator) {
            $logs = array();

            do {
                $priority = Logger::ERR;
                if ($exception instanceof \ErrorException && isset(Logger::$errorPriorityMap[$exception->getSeverity()])) {
                    $priority = Logger::$errorPriorityMap[$exception->getSeverity()];
                }

                $extra = array(
                    'type'  => 'EXCEPTION',
                    'file'  => $exception->getFile(),
                    'line'  => $exception->getLine(),
                    'trace' => $exception->getTrace(),
                    'reference' => $generator->generate()
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
                $logger->log($log['priority'], $log['message'], $log['extra']);
            }
            // return false to not continue other handlers
            return false;
        });
    }


    public function setMvcErrorHandler($logger, $generator)
    {

    }

    public function setFatalErrorHandler($logger, $generator, $config)
    {
        // catch also fatal errors which would not show the regular error template.
        register_shutdown_function(function () use ($logger, $generator, $config) {
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

            $errorReference = $generator->generate();

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
            $logger->log($logType, $error['message'], $extras);

            // get absolute path of the template to render (the shutdown method sometimes changes relative path).
            $fatalTemplatePath = dirname(__FILE__) . '/view/error/fatal.html';
            if (!empty($config['dherrorlogging']['templates']['fatal'])
                && file_exists($config['dherrorlogging']['templates']['fatal'])
            ) {

                $fatalTemplatePath = $config['dherrorlogging']['templates']['fatal'];
            }
            // read content of file
            $body = file_get_contents($fatalTemplatePath);
            // inject error reference
            $body = str_replace('%__ERROR_REFERENCE__%', 'Error Reference: ' . $errorReference, $body);
            echo $body;
            exit();
        });
    }
}
