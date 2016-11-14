<?php
/**
 * @copyright  Copyright 2009-2015 Davidhavl.com
 * @license    MIT , http://davidhavl.com/license/MIT
 * @author     davidhavl
 */
namespace DhErrorLogging\Logger\Factory;

use Interop\Container\ContainerInterface;
use Zend\Log\LoggerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Log\Logger;
use Zend\Log\Writer;
use Zend\Log\Filter;


class LoggerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @return LoggerInterface
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {

        // create new logger
        $logger = new Logger();

        // get application config as array
        $config = $container->get('config');
        $config = $config['dherrorlogging'];

        // get priority
        $priority =  Logger::WARN;
        if (isset($config['priority']) && is_int($config['priority'])) {
            $priority = $config['priority'];
        }
        $priorityFilter = new Filter\Priority($priority);
        // get writers from config
        if (!empty($config['log_writers']) && is_array($config['log_writers'])) {
            $logWriterManager = $container->get('LogWriterManager');
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
        $processor = $container->get('LogProcessorManager')->get('DhErrorLogging\LoggerProcessor');
        $logger->addProcessor($processor);

        // Logger needs at least one writer. Check if there are any, else add empty one.
        if ($logger->getWriters()->count() == 0) {
            $logger->addWriter(new Writer\Noop());
        }

        return $logger;
    }

    /**
     * {@inheritDoc}
     *
     * For use with zend-servicemanager v2; proxies to __invoke().
     */
    public function createService(ServiceLocatorInterface $container)
    {
        // Retrieve the parent container when under zend-servicemanager v2
        if (method_exists($container, 'getServiceLocator')) {
            $container = $container->getServiceLocator() ?: $container;
        }

        return $this($container, LoggerInterface::class);
    }
}
