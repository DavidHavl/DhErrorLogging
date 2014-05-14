<?php

namespace ErrorLogging;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Log\Logger;
use Zend\Log\Processor;
use Zend\Log\Writer;
use Zend\Log\Formatter;
use Zend\Log\Filter;
use Zend\Db\Adapter;

class Module
{

    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);


        // set logging of errors
        $this->initErrorLogger($e);
    }


    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }



    public function getLogProcessorConfig()
    {
        return array(
            'factories' => array(
                'Errorlogging\Processor\LogExtras' => function($sm) {
                        $request = $sm->getServiceLocator()->get('application')->getRequest();
                        $processor = new \ErrorLogging\Processor\LogExtras($request);
                        return $processor;
                    }
            )
        );
    }

    public function initErrorLogger($e)
    {
        $app = $e->getApplication();
        $eventManager = $app->getEventManager();
        $serviceManager = $app->getServiceManager();

        // create new logger
        $logger = new Logger();
        // create priority filter to log only certain error types (warnings or more serious error types).
        $filter = new Filter\Priority(Logger::WARN);


        //// add processors which will add some extra helpful info to the final log. ////

        // add backtrace to final log
        $logger->addProcessor(new Processor\Backtrace());
        // add extra info (IP, URI, trace) to the final log.
        $logger->addProcessor($serviceManager->get('LogProcessorManager')->get('Errorlogging\Processor\LogExtras'));




        //// logging into file ////

        // set path of a file where logs will be written to
        $filePath = APPLICATION_PATH . '/data/log/error.log';
        // create new log writer - stream - to log errors to file.
        $streamWriter = new Writer\Stream($filePath, null, "\n");
        // add filter to log only warnings or more serious errors
        $streamWriter->addFilter($filter);
        // add the new writer to logger
        $logger->addWriter($streamWriter);


        //// logging into db as well (delete this section if logging in to file is good enough)  ////

        // get db adapter from service manager
        $dbAdapter = $serviceManager->get('Zend\Db\Adapter\Adapter');
        // set db table name where logs will be recorded to
        $dbTableName = 'error_log';

        // create a map between errors and your db table columns
        $map = array(
            'timestamp' => 'creation_date',
            'priorityName' => 'priority',
            'message' => 'message',
            'extra' =>  array(
                'file'  => 'file',
                'line'  => 'line',
                'trace' => 'trace',
                'xdebug' => 'xdebug',
                'uri' => 'uri',
                'ip' => 'ip',
                'session_id' => 'session_id'
            )
        );

        // create new database writer
        $dbWriter = new Writer\Db($dbAdapter, $dbTableName, $map);
        // add filter to log only wanted error types
        $dbWriter->addFilter($filter);
        // add db writer to logger
        $logger->addWriter($dbWriter);



        //// logging into chromePhp ////

        // check if we are in development mode (make sure APPLICATION_ENV is defined)
        if (APPLICATION_ENV == 'development') {
            // create chromephp writer
            $chromeWriter = new Writer\ChromePhp();
            // add bit more info about the error to the chrome console, not just message
            $chromeWriter->setFormatter(new Formatter\ErrorHandler());
            // add it to logger
            $logger->addWriter($chromeWriter);
        }


        // Handle native PHP errors
        Logger::registerErrorHandler($logger, true);
        Logger::registerExceptionHandler($logger);
        Logger::registerFatalErrorShutdownFunction($logger);


        // Handle framework specific errors
        $eventManager->attach(array(MvcEvent::EVENT_DISPATCH_ERROR, MvcEvent::EVENT_RENDER_ERROR), function($event) use ($logger) {
            // check if event is error
            if (!$event->isError()) {
                return;
            }
            // get message and exception (if present)
            $message = $event->getError();
            $exception = $event->getParam('exception');

            $extras = array();
            // check if event has exception and populate extras array.
            if (!empty($exception)) {
                $message =        $exception->getMessage();
                $extras['file'] =  $exception->getFile();
                $extras['line']  = $exception->getLine();
                $extras['trace'] = $exception->getTraceAsString();
            }
            // log the error
            $logger->log(Logger::ERR, $message, $extras);
        });
    }




}