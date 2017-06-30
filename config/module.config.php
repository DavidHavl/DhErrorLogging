<?php
use Zend\Log\Logger;

return [

    'dherrorlogging' => [
        'enabled' => true,

        // error types to be logged
        'error_types' => [
            // Exceptions (those other than within dispatch or render phase)
            'exceptions' => true,
            // Native PHP errors (those other than within dispatch or render phase)
            'errors' => true,
            // Dispatch errors, triggered in case of a problem or exception anywhere during dispatch process (unknown controller, exception thrown inside of controller,...)
            'dispatch' => true,
            // Router no match  (route not found = 404).
            'dispatch\router_no_match' => true,
            // Render errors, triggered in case of a problem during the render process (no renderer found...).
            'render' => true,
            // Fatal errors that halt execution of further code
            'fatal' => true,
        ],
        // filter out some of the exception types (i.e. \Exception\UnauthorizedException)
        'exception_filter' => [
            '\Exception\UnauthorizedException' // 403
        ],
        // Levels of errors which will show the nice error view defined in template under [templates][error] section
        'displayable_error_levels' => E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT,

        // set writers to be used.
        // You can either add new config array for some of the the standard writers that don't need injection of other objects (stream, chromephp, 'fingerscrossed', 'firephp', 'mail', 'mock', 'null', 'syslog', 'zendmonitor')
        // or identifier of registered log writer factory (registered in config section ['log_writers']).

        'log_writers' => [
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

        ],

        // Paths of templates to be used for output
        'templates' => [
            'dispatch' => __DIR__ . '/../view/error/dispatch.phtml',
            'render' => __DIR__ . '/../view/error/render.phtml',
            'exception' => __DIR__ . '/../view/error/exception.html',
            'error' => __DIR__ . '/../view/error/error.html',
            'fatal' => __DIR__ . '/../view/error/fatal.html',
            'console' => __DIR__ . '/../view/error/console.txt',
            'json' => __DIR__ . '/../view/error/json.js',
        ]
    ],

    'log_writers' => [
        'factories' => [
            'DhErrorLogging\DbWriter' => 'DhErrorLogging\Writer\Factory\DbWriterFactory',
            'DhErrorLogging\DoctrineWriter' => 'DhErrorLogging\Writer\Factory\DoctrineWriterFactory'
        ]
    ],

    'log_processors' => [
        'factories' => [
            'DhErrorLogging\LoggerProcessor' => 'DhErrorLogging\Processor\Factory\ExtrasProcessorFactory'
        ]
    ],

    'service_manager' => [
        'factories' => [
            'DhErrorLogging\Logger' => 'DhErrorLogging\Logger\Factory\LoggerFactory',
            'DhErrorLogging\Filter\ExceptionFilter' => 'DhErrorLogging\Filter\Factory\ExceptionFilterFactory',
            'DhErrorLogging\Generator\ErrorReferenceGenerator' => 'DhErrorLogging\Generator\Factory\ErrorReferenceGeneratorFactory',
            'DhErrorLogging\Options\ModuleOptions' => 'DhErrorLogging\Options\Factory\ModuleOptionsFactory',
            'DhErrorLogging\Sender\ResponseSender' => 'DhErrorLogging\Sender\Factory\ResponseSenderFactory'
        ],
    ],

    'doctrine' => [
        'driver' => [
            'dherrorlogging_entity' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [
                    realpath(__DIR__ . '/../src/DhErrorLogging/Doctrine/Entity')
                ]
            ],

            'orm_default' => [ // if your driver name is different from please change it here.
                'drivers' => [
                    'DhErrorLogging\Doctrine\Entity'  => 'dherrorlogging_entity'
                ]
            ]
        ]
    ],
];
