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
        // grab the service locator
        $sl = $serviceLocator->getServiceLocator();

        // get zend db adapter
        $dbAdapter = $sl->get('dherrorlogging_zend_db_adapter');

        // get logger db config
        $config = $sl->get('Config')['dherrorlogging'];

        // set db table name where logs will be recorded to
        $dbTableName = $config['log_writers']['db']['table_name'];

        // create a map between errors and your db table columns
        $map = $config['log_writers']['db']['table_map'];

        // create new database writer
        $dbWriter = new Writer\Db($dbAdapter, $dbTableName, $map);

        // add filter to log only wanted error types
        $filter = new Filter\Priority($config['priority']);
        $dbWriter->addFilter($filter);

        return $dbWriter;
    }
}
