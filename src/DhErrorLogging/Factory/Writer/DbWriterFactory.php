<?php
/**
 * @copyright  Copyright 2009-2014 Davidhavl.com
 * @license    MIT , http://davidhavl.com/license/MIT
 * @author     davidhavl
 */
namespace DhErrorLogging\Factory\Writer;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Log\Writer;
use Zend\Log\Filter;
use Zend\Log\Logger;

class DbWriterFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $dbAdapter = $serviceLocator->getServiceLocator()->get('dherrorlogging_zend_db_adapter');

        // set db table name where logs will be recorded to
        $dbTableName = 'error_log';

        // create a map between errors and your db table columns
        $map = array(
            'timestamp' => 'creation_time',
            'priorityName' => 'priority',
            'message' => 'message',
            'extra' =>  array(
                'reference'  => 'reference',
                'file'  => 'file',
                'line'  => 'line',
                'trace' => 'trace',
                'xdebug' => 'xdebug',
                'uri' => 'uri',
                //'request' => 'request',
                'ip' => 'ip',
                'session_id' => 'session_id'
            )
        );
        // create new database writer
        $dbWriter = new Writer\Db($dbAdapter, $dbTableName, $map);
        // add filter to log only wanted error types
        $filter = new Filter\Priority(Logger::WARN);
        $dbWriter->addFilter($filter);
        return $dbWriter;
    }
}
