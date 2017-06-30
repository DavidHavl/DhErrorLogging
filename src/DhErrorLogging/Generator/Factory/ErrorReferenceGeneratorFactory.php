<?php
/**
 * @copyright  Copyright  DavidHavl.com
 * @license    MIT , http://DavidHavl.com/license/MIT
 * @author     davidhavl
 */
namespace DhErrorLogging\Generator\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use DhErrorLogging\Generator\ErrorReference;

class ErrorReferenceGeneratorFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @return ErrorReference
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new ErrorReference();
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

        return $this($container, ErrorReference::class);
    }
}
