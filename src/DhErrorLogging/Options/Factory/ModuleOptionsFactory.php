<?php
/**
 * @copyright  Copyright 2009-2015 Davidhavl.com
 * @license    MIT , http://davidhavl.com/license/MIT
 * @author     davidhavl
 */
namespace DhErrorLogging\Options\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use DhErrorLogging\Options\ModuleOptions;

class ModuleOptionsFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     * @return ModuleOptions
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {   $config = $serviceLocator->get('config');
        return new ModuleOptions($config['dherrorlogging']);
    }
}
