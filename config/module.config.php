<?php
use Zend\Log\Logger;

return array(

    'dherrorlogging' => array(
        'enabled' => true,
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
            'db' => array(
//                'name' => 'DhErrorLogging\DbWriter',
                'table_name' => 'error_log',
//                'table_map' => array(
//                    'timestamp' => 'creation_time',
//                    'priorityName' => 'priority',
//                    'message' => 'message',
//                    'extra' =>  array(
//                        'reference'  => 'reference',
//                        'file'  => 'file',
//                        'line'  => 'line',
//                        'trace' => 'trace',
//                        'xdebug' => 'xdebug',
//                        'uri' => 'uri',
//                        'request' => 'request',
//                        'ip' => 'ip',
//                        'session_id' => 'session_id'
//                    )
//                )
            )

        ),

        'priority' => Logger::WARN,

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
