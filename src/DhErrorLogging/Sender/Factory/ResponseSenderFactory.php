<?php
/**
 * @copyright  Copyright 2009-2015 Davidhavl.com
 * @license    MIT , http://davidhavl.com/license/MIT
 * @author     davidhavl
 */
namespace DhErrorLogging\Sender\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use DhErrorLogging\Sender\ResponseSender;

class ResponseSenderFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @return ResponseSender
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        // get request
        $request = $container->get('Request');
        $response = $container->get('Response');
        $options = $container->get('DhErrorLogging\Options\ModuleOptions');

        return new ResponseSender($request, $response, $options);
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

        return $this($container, ResponseSender::class);
    }
}
