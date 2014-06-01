<?php
/**
 * @copyright  Copyright 2009-2014 Davidhavl.com
 * @license    MIT , http://davidhavl.com/license/MIT
 * @author     davidhavl
 */
namespace DhErrorLogging\Factory\Generator;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use DhErrorLogging\Generator\ErrorReference;

class ErrorReferenceGeneratorFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new ErrorReference();
    }
}
