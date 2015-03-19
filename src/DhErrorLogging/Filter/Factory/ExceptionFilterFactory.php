<?php
/**
 * @copyright  Copyright 2009-2015 Davidhavl.com
 * @license    MIT , http://davidhavl.com/license/MIT
 * @author     davidhavl
 */
namespace DhErrorLogging\Filter\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use DhErrorLogging\Filter\ExceptionFilter;

class ExceptionFilterFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config');
        $config = $config['dherrorlogging'];

        $blacklist = array();

        if (!empty($config['exception_filter'])) {
            $blacklist = $config['exception_filter'];
        }

        return new ExceptionFilter($blacklist);
    }
}
