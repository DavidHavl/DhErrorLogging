<?php
/**
 * @copyright  Copyright  DavidHavl.com
 * @license    MIT , http://DavidHavl.com/license/MIT
 * @author     davidhavl
 */
namespace DhErrorLogging\Processor\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use DhErrorLogging\Processor\Extras;

class ExtrasProcessorFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @return Extras
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        // get request
        $request = $container->get('Request');
        // inject request into the extras processor
        $processor = new Extras($request);
        return $processor;
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

        return $this($container, Extras::class);
    }
}
