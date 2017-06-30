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
use Zend\Log\Writer;

class DbWriterFactory implements FactoryInterface
{
    protected $options = array();

    public function __construct($options = array())
    {
        $this->options = $options;
    }

    /**
     * {@inheritDoc}
     *
     * @return Writer\Db
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        // get zend db adapter
        $dbAdapter = $container->get('dherrorlogging_zend_db_adapter');

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

        return $this($container, Writer\Db::class);
    }
}
