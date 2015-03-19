<?php
use Zend\Log\Logger;

return array(

    'dherrorlogging' => array(
        'enabled' => true,

        // error types to be logged
        'error_types' => array(
            // Exceptions (those other than within dispatch or render phase)
            'exceptions' => true,
            // Native PHP errors
            'native' => true,
            // Dispatch errors, triggered in case of a problem or exception anywhere during dispatch process (unknown controller, exception thrown inside of controller,...)
            'dispatch' => true,
            // Router no match  (route not found = 404).
            'dispatch\router_no_match' => true,
            // Render errors, triggered in case of a problem during the render process (no renderer found...).
            'render' => true,
            // Fatal errors that halt execution of further code
            'fatal' => true,
        ),
        // filter out some of the exception types (i.e. \Exception\UnauthorizedException)
        'exception_filter' => array(
            '\Exception\UnauthorizedException' // 403
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
            'DhErrorLogging\DbWriter' => 'DhErrorLogging\Writer\Factory\DbWriterFactory'
        )
    ),

    'log_processors' => array(
        'factories' => array(
            'DhErrorLogging\LoggerProcessor' => 'DhErrorLogging\Processor\Factory\ExtrasProcessorFactory'
        )
    ),

    'service_manager' => array(

        'aliases' => array(
            'dherrorlogging_zend_db_adapter' => 'Zend\Db\Adapter\Adapter',
        ),

        'factories' => array(
            'DhErrorLogging\Logger' => 'DhErrorLogging\Logger\Factory\LoggerFactory',
            'DhErrorLogging\Filter\ExceptionFilter' => 'DhErrorLogging\Filter\Factory\ExceptionFilterFactory',
            'DhErrorLogging\Generator\ErrorReferenceGenerator' => 'DhErrorLogging\Generator\Factory\ErrorReferenceGeneratorFactory',
            'DhErrorLogging\Options\ModuleOptions' => 'DhErrorLogging\Options\Factory\ModuleOptionsFactory'
        ),


    ),
);
