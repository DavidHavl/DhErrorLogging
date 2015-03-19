<?php
/**
 * @copyright  Copyright 2009-2015 Davidhavl.com
 * @license    MIT , http://davidhavl.com/license/MIT
 * @author     davidhavl
 */
namespace DhErrorLogging\Processor\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use DhErrorLogging\Processor\Extras;

class ExtrasProcessorFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        // get request
        $request = $serviceLocator->getServiceLocator()->get('Request');
        // inject request into the extras processor
        $processor = new Extras($request);
        return $processor;
    }
}
