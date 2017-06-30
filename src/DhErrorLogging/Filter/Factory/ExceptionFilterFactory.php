<?php
/**
 * @copyright  Copyright 2009-2016 DavidHavl.com
 * @license    MIT , http://DavidHavl.com/license/MIT
 * @author     davidhavl
 */
namespace DhErrorLogging\Filter\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use DhErrorLogging\Filter\ExceptionFilter;

class ExceptionFilterFactory implements FactoryInterface
{

    /**
     * {@inheritDoc}
     *
     * @return ExceptionFilter
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $config = $container->get('config');
        $config = $config['dherrorlogging'];

        $blacklist = array();

        if (!empty($config['exception_filter'])) {
            $blacklist = $config['exception_filter'];
        }

        return new ExceptionFilter($blacklist);
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

        return $this($container, ExceptionFilter::class);
    }
}
