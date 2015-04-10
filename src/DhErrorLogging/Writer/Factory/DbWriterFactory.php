<?php
/**
 * @copyright  Copyright 2009-2015 Davidhavl.com
 * @license    MIT , http://davidhavl.com/license/MIT
 * @author     davidhavl
 */
namespace DhErrorLogging\Writer\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Log\Writer;
use Zend\Log\Filter;

class DbWriterFactory implements FactoryInterface
{
    protected $options = array();

    public function __construct($options = array())
    {
        $this->options = $options;
    }
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        // grab the service locator
        $sl = $serviceLocator->getServiceLocator();

        // get zend db adapter
        $dbAdapter = $sl->get('dherrorlogging_zend_db_adapter');

        // set db table name where logs will be recorded to
        $dbTableName = 'error_log';
        // check if there is a setting that overwrites the default table name
        if (!empty($this->options['table_name'])) {
            $dbTableName = $this->options['table_name'];
        }

        // create a map between errors and db table columns
        $map = array(
            'timestamp'      => 'creation_time',
            'priorityName'   => 'priority',
            'message'        => 'message',
            'extra' => array(
                'type'       => 'type',
                'reference'  => 'reference',
                'file'       => 'file',
                'line'       => 'line',
                'trace'      => 'trace',
                'xdebug'     => 'xdebug',
                'uri'        => 'uri',
                'request'    => 'request',
                'ip'         => 'ip',
                'session_id' => 'session_id'
            )
        );
        // check if there is a setting that overwrites the default table map
        if (!empty($this->options['table_map'])) {
            $mapTemp = $this->options['table_map'];
            //check first level fields and convert rest as extras
            $mainFields = array('timestamp','priority','priorityName','message');
            foreach ($mapTemp as $key=>$value) {
                if (!in_array($key, $mainFields)) {
                    if (!isset($mapTemp['extra'])) {
                        $mapTemp['extra'] = array();
                    }
                    $mapTemp['extra'][$key] = $value;
                    unset($mapTemp[$key]);
                }
            }
            $map = $mapTemp;
        }

        // create new database writer
        $dbWriter = new Writer\Db($dbAdapter, $dbTableName, $map);

        return $dbWriter;
    }
}
