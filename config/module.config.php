<?php
use Zend\Log\Logger;

return array(

    'dherrorlogging' => array(
        'enabled' => true,
        // set writers to be used.
        // db, stream, chromephp, 'fingerscrossed', 'firephp', 'mail', 'mock', 'null', 'syslog', 'zendmonitor'
        'log_writers' => array(
            'stream' => array(
                'name' => 'stream',
                'options' => array(
                    'stream' => 'data/log/error.log',
                    'log_separator' => "\n"
                ),
                'priority' => Logger::WARN
            ),

        ),
        'templates' => array(
            'fatal' => __DIR__ . '/../view/error/fatal.html',
        )
    ),

    'log_processors' => array(
        'factories' => array(
            'DhErrorLogging\LoggerProcessor' => 'DhErrorLogging\Factory\Processor\ExtrasProcessorFactory'
        )
    ),

    'service_manager' => array(
        'factories' => array(
            'DhErrorLogging\Logger' => 'DhErrorLogging\Factory\Logger\LoggerFactory',
            'DhErrorLogging\ErrorReferenceGenerator' => 'DhErrorLogging\Factory\Generator\ErrorReferenceGeneratorFactory'
        )
    ),
);