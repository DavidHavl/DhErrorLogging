<?php

namespace DhErrorLogging;

use Zend\Mvc\MvcEvent;
use Zend\Log\Logger;
use Zend\Db\Adapter;
use Zend\ModuleManager\Feature;

class Module implements
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

        // convert php errors into exceptions so that it is caught by same process as exceptions.
        set_error_handler(function ($level, $message, $file, $line) use ($logger) {
            $minErrorLevel = error_reporting();
            if ($minErrorLevel & $level) {
                throw new \ErrorException($message, $code = 0, $level, $file, $line);
            }
            // return false to not continue native handler
            return false;
        });

        // Get shared event manager
        $sharedEventManager  = $app->getEventManager()->getSharedManager();
        // Handle framework specific errors
        $sharedEventManager->attach('Zend\Mvc\Application', array(MvcEvent::EVENT_DISPATCH_ERROR, MvcEvent::EVENT_RENDER_ERROR), function($event) use ($logger, $generator) {

            // check if event is error
            if (!$event->isError()) {
                return;
            }

            $errorType = E_ERROR;
            // get message and exception (if present)
            $message = $event->getError();
            $exception = $event->getParam('exception');
            // generate unique reference for this error
            $errorReference = $generator->generate();
            $extras = array(
                'reference' => $errorReference
            );
            // check if event has exception and populate extras array.
            if (!empty($exception)) {
                $message =        $exception->getMessage();
                $extras['file'] =  $exception->getFile();
                $extras['line']  = $exception->getLine();
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
            $originalMessage = $event->getResult()->getVariable('message');
            $event->getResult()->setVariable('message', $originalMessage . '<br /> Error Reference: ' .  $errorReference);

        });

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
            while( ob_get_level() > 0 ) {
                ob_end_clean();
            }

            $errorReference = $generator->generate();

            $extras = array(
                'reference' => $errorReference,
                'file' => $error['file'],
                'line' => $error['line']
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
                && file_exists($config['dherrorlogging']['templates']['fatal'])) {

                $fatalTemplatePath = $config['dherrorlogging']['templates']['fatal'];
            }
            // read content of file
            $body = file_get_contents($fatalTemplatePath);
            // inject error reference
            $body = str_replace('%__ERROR_REFERENCE__%', 'Error Reference: ' .  $errorReference, $body);
            echo $body;
            die(1);
        });

    }

}
