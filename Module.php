<?php

namespace DhErrorLogging;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Log\Logger;
use Zend\Db\Adapter;

class Module
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
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);


        // set logging of errors
        $this->initErrorLogger($e);
    }

    public function initErrorLogger($e)
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
        $sharedEventManager->attach('Zend\Mvc\Application', array(MvcEvent::EVENT_DISPATCH_ERROR, MvcEvent::EVENT_RENDER_ERROR), function($event) use ($logger, $generator, $config) {

            // check if event is error
            if (!$event->isError()) {
                return;
            }
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
                $extras['trace'] = $exception->getTraceAsString();

                // check if xdebug is enabled and message present in which case add it to the extras
                if (isset($exception->xdebug_message)) {
                    $extra['xdebug'] = $exception->xdebug_message;
                }
            }

            // log it
            $priority = $config['dherrorlogging']['priority'];
            $logger->log($priority, $message, $extras);

            // hijack error view and add error reference to the message
            $originalMessage = $event->getResult()->getVariable('message');
            $event->getResult()->setVariable('message', $originalMessage . '<br /> Error Reference: ' .  $errorReference);

        });

        // catch also fatal errors which woud not show the error template.
        register_shutdown_function(function () use ($logger, $generator, $config) {

            $error = error_get_last();
            // log only errors
            if (null === $error || !isset($error['type']) || $error['type'] !== E_ERROR) {
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

            // log error using logger
            $logger->log(Logger::$errorPriorityMap[$error['type']], $error['message'], $extras);

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
