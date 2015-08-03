<?php
/**
 * @copyright  Copyright 2009-2015 Davidhavl.com
 * @license    MIT , http://davidhavl.com/license/MIT
 * @author     davidhavl
 */
namespace DhErrorLogging\Logger\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Log\Logger;
use Zend\Log\Processor;
use Zend\Log\Writer;
use Zend\Log\Formatter;
use Zend\Log\Filter;


class LoggerFactory implements FactoryInterface
{

    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {

        // create new logger
        $logger = new Logger();

        // get application config as array
        $config = $serviceLocator->get('config');
        $config = $config['dherrorlogging'];

        // get priority
        $priority =  Logger::WARN;
        if (isset($config['priority']) && is_int($config['priority'])) {
            $priority = $config['priority'];
        }
        $priorityFilter = new Filter\Priority($priority);
        // get writers from config
        if (!empty($config['log_writers']) && is_array($config['log_writers'])) {
            $logWriterManager = $serviceLocator->get('LogWriterManager');
            foreach ($config['log_writers'] as $writerSpecs) {
                // skip if no name
                if(empty($writerSpecs['name']) || !is_string($writerSpecs['name'])) {
                   continue;
                }
                // get options
                $options = array();
                if (!empty($writerSpecs['options']) && is_array($writerSpecs['options'])) {
                    $options = $writerSpecs['options'];
                }

                // check if it is one of the known writers that can be created via config or retrieved from service manager
                if ($logWriterManager->has($writerSpecs['name'])) {
                    $writer = $logWriterManager->get($writerSpecs['name'], $options);
                    // add priority filter
                    $writer->addFilter($priorityFilter);
                    // add writer to logger
                    $logger->addWriter($writer);
                }

            }
        }

        // add processors which will add some extra helpful info (IP, URI, trace,..) to the final log.
        $processor = $serviceLocator->get('LogProcessorManager')->get('DhErrorLogging\LoggerProcessor');
        $logger->addProcessor($processor);

        // Logger needs at least one writer. Check if there are any, else add empty one.
        if ($logger->getWriters()->count() == 0) {
            // Support for PHP7 / Zend >= 2.4
            if (class_exists('\Zend\Log\Writer\Noop')) {
                $logger->addWriter(new Writer\Noop());
            } else {
                $logger->addWriter(new Writer\Null());
            }
        }

        return $logger;
    }
}
