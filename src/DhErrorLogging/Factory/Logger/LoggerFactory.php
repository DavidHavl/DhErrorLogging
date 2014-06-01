<?php
/**
 * @copyright  Copyright 2009-2014 Davidhavl.com
 * @license    MIT , http://davidhavl.com/license/MIT
 * @author     davidhavl
 */
namespace DhErrorLogging\Factory\Logger;

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
        // get application config as array
        $config = $serviceLocator->get('Config');
        $config = $config['dherrorlogging'];
        // get writers from config
        $writersConfig =  (!empty($config['log_writers'])?$config['log_writers']:array());

        // create new logger
        $logger = new Logger(array('writers' => $writersConfig));


        // add processors which will add some extra helpful info (IP, URI, trace,..) to the final log.
        $processor = $serviceLocator->get('LogProcessorManager')->get('DhErrorLogging\LoggerProcessor');
        $logger->addProcessor($processor);

        // Logger needs at least one writer. Check if there are any, else add empty one.
        if ($logger->getWriters()->count() == 0) {
            $logger->addWriter(new Writer\Null());
        }

        return $logger;
    }
}
