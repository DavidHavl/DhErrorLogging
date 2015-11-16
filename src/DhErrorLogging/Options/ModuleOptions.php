<?php
/**
 * Options for DhErrorLogging module
 *
 * @author     David Havl <contact@davidhavl.com>
 * @copyright  Copyright 2004-2015 David Havl
 * @license    MIT , http://davidhavl.com/license/MIT
 * @link       http://davidhavl.com
 */


namespace DhErrorLogging\Options;

use Zend\Stdlib\AbstractOptions;

/**
 * Options for DhErrorLogging module
 */
class ModuleOptions extends AbstractOptions
{
    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @var array
     */
    protected $errortypes;

    /**
     * @var array
     */
    protected $exceptionfilter;

    /**
     * @var array
     */
    protected $logwriters;

    /**
     * @var array
     */
    protected $templates;

    /**
     * @var int
     */
    protected $displayableerrorlevels = E_ALL;

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param boolean $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return array
     */
    public function getErrortypes()
    {
        return $this->errortypes;
    }

    /**
     * @param array $errortypes
     */
    public function setErrortypes($errortypes)
    {
        $this->errortypes = $errortypes;
    }

    /**
     * Get if error type is enabled
     * @param string $key
     * @return bool
     */
    public function isErrortypeEnabled($key)
    {
        if (!empty($this->errortypes[$key])) {
            return true;
        }
        return false;
    }


    /**
     * @return array
     */
    public function getExceptionfilter()
    {
        return $this->exceptionfilter;
    }

    /**
     * @param array $exceptionfilter
     */
    public function setExceptionfilter($exceptionfilter)
    {
        $this->exceptionfilter = $exceptionfilter;
    }

    /**
     * @return array
     */
    public function getLogwriters()
    {
        return $this->logwriters;
    }

    /**
     * @param array $logwriters
     */
    public function setLogwriters($logwriters)
    {
        $this->logwriters = $logwriters;
    }

    /**
     * @return array
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * @param mixed $templates
     */
    public function setTemplates($templates)
    {
        $this->templates = $templates;
    }

    /**
     * Get template by key
     * @param string $key key of the template
     * @return string
     */
    public function getTemplate($key)
    {
        if (isset($this->templates[$key])) {
            return $this->templates[$key];
        }
        return null;
    }

    /**
     * This is here just for BC and has no effect
     * @param mixed $value
     */
    public function setPriority($value) {
        // this is here just for BC and has no effect
    }

    /**
     * Get level of errors in whitch nice view is presented
     * @return int
     */
    public function getDisplayableErrorLevels() {
        return $this->displayableerrorlevels;
    }

    public function setDisplayableErrorLevels($value) {
        $this->displayableerrorlevels = (int)$value;
    }
}