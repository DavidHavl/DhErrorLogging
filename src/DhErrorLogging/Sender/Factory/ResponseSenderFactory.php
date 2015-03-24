<?php
/**
 * @copyright  Copyright 2009-2015 Davidhavl.com
 * @license    MIT , http://davidhavl.com/license/MIT
 * @author     davidhavl
 */
namespace DhErrorLogging\Sender\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use DhErrorLogging\Sender\ResponseSender;

class ResponseSenderFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        // get request
        $request = $serviceLocator->get('Request');
        $response = $serviceLocator->get('Response');
        $options = $serviceLocator->get('DhErrorLogging\Options\ModuleOptions');
        //var_dump($response);die();

        return new ResponseSender($request, $response, $options);
    }
}
