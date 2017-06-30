<?php
/**
 * @copyright  Copyright  DavidHavl.com
 * @license    MIT , http://DavidHavl.com/license/MIT
 * @author     davidhavl
 */
namespace DhErrorLogging\Writer\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use DhErrorLogging\Writer\DoctrineWriter;

class DoctrineWriterFactory implements FactoryInterface
{
    protected $options = array();

    public function __construct($options = array())
    {
        $this->options = $options;
    }

    /**
     * {@inheritDoc}
     *
     * @return DoctrineWriter
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        // get entity manager
        $em = $container->get('dherrorlogging_doctrine_entity_manager');
        return new DoctrineWriter($em);
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

        return $this($container, DoctrineWriter::class);
    }
}
