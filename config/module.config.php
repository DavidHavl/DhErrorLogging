<?php
use Zend\Log\Logger;

return array(

    'dherrorlogging' => array(
        'enabled' => true,

        // error types to be logged
        'error_types' => array(
            // Exceptions (those other than within dispatch or render phase)
            'exception' => true,
            // Native PHP errors
            'native' => true,
            // Dispatch errors, triggered in case of a problem during dispatch process (unknown controller...) (404, 403,...)
            'dispatch' => true,
            // Render errors, triggered in case of a problem during the render process (no renderer found...).
            'render' => true,
            // Fatal errors that halt execution of further code
            'fatal' => true,
        ),
        // set writers to be used.
        // You can either add new config array for some of the the standard writers that don't need injection of other objects (stream, chromephp, 'fingerscrossed', 'firephp', 'mail', 'mock', 'null', 'syslog', 'zendmonitor')
        // or identifier of registered log writer factory (registered in config section ['log_writers']).

        'log_writers' => array(
            //'stream' => array(
            //    'name' => 'stream',
            //    'options' => array(
            //        'stream' => 'data/log/error.log',
            //        'log_separator' => "\n"
            //    ),
            //
            //),
//            'db' => array(
//               'name' => 'DhErrorLogging\DbWriter',
//               'options' => array(
//                   'table_name' => 'error_log',
//                   'table_map' => array(
//                       'timestamp' => 'creation_time',
//                       'type' => 'type'
//                       'priorityName' => 'priority',
//                       'message' => 'message',
//                       'reference'  => 'reference',
//                       'file'  => 'file',
//                       'line'  => 'line',
//                       'trace' => 'trace',
//                       'xdebug' => 'xdebug',
//                       'uri' => 'uri',
//                       'request' => 'request',
//                       'ip' => 'ip',
//                       'session_id' => 'session_id'
//                   )
//               )
//            )

        ),

        
        'templates' => array(
            'fatal' => __DIR__ . '/../view/error/fatal.html',
        )
    ),

    'log_writers' => array(
        'factories' => array(
            'DhErrorLogging\DbWriter' => 'DhErrorLogging\Factory\Writer\DbWriterFactory'
        )
    ),

    'log_processors' => array(
        'factories' => array(
            'DhErrorLogging\LoggerProcessor' => 'DhErrorLogging\Factory\Processor\ExtrasProcessorFactory'
        )
    ),

    'service_manager' => array(

        'aliases' => array(
            'dherrorlogging_zend_db_adapter' => 'Zend\Db\Adapter\Adapter',
        ),

        'factories' => array(
            'DhErrorLogging\Logger' => 'DhErrorLogging\Factory\Logger\LoggerFactory',
            'DhErrorLogging\ErrorReferenceGenerator' => 'DhErrorLogging\Factory\Generator\ErrorReferenceGeneratorFactory'
        ),


    ),
);
