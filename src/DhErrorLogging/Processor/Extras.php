<?php
/**
 * @copyright  Copyright 2009-2015 Davidhavl.com
 * @license    MIT , http://davidhavl.com/license/MIT
 * @author     davidhavl
 */

namespace DhErrorLogging\Processor;

use Zend\Log\Processor\ProcessorInterface;
use Zend\Stdlib\RequestInterface;
use Zend\Http\PhpEnvironment\RemoteAddress;
use Zend\Http\Request as HttpRequest;

class Extras implements ProcessorInterface
{
    protected $request = null;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }


    /**
     * Adds IP, uri and other details to the event extras
     *
     * @param array $event event data
     * @return array event data
     */
    public function process(array $event)
    {

        $uri = '';
        $request = null;
        if ($this->request instanceof HttpRequest) {
            $uri = $this->request->getUriString();
        }
        if (method_exists($this->request, 'toString')) {
            $request = $this->request->toString();
        }
        // get request uri and IP address and add it to the extras of the logger
        $remoteAddress = new RemoteAddress();
        $extras = array(
            'uri' => $uri,
            'request' => $request,
            'ip' => $remoteAddress->getIpAddress(),
            'session_id' => session_id(),
        );
        if (isset($event['extra']) && is_array($event['extra'])) {
            $extras = array_merge($event['extra'], $extras);
        }
        $event['extra'] = $extras;

         // check if we have trace, else get it explicitly
        if (empty($event['extra']['trace'])) {
            $event['extra']['trace'] = $this->getTrace();
        }

        // check if trace is an array and format it as simple string
        if (is_array($event['extra']['trace'])) {

            $traceString = '';
            $trace = $event['extra']['trace'];
            $index = 1;
            for ($i = 0; $i < count($trace); $i++) {
                if (isset($trace[$i]['class']) && (false !== strpos($trace[$i]['class'], 'Zend\\Log')
                || false !== strpos($trace[$i]['class'], 'DhErrorLogging'))) {
                    continue;
                }
                $traceString .= '#' . $index
                    . (isset($trace[$i-1]['file'])?$trace[$i-1]['file']:(($i== 0 && !empty($event['extra']['file']))?$event['extra']['file']:''))
                    . "(" . (isset($trace[$i-1]['line'])?$trace[$i-1]['line']:(($i== 0 && !empty($event['extra']['line']))?$event['extra']['line']:'')) . "): "
                    . (isset($trace[$i]['class'])?$trace[$i]['class']:'')
                    . (isset($trace[$i]['type'])?$trace[$i]['type']:' ')
                    . (isset($trace[$i]['function'])?$trace[$i]['function']:'')
                    . "\n"; // add new line for file logs
                ;
                $index++;
            }

            $event['extra']['trace'] = $traceString . "\n\n"; // add 2x new line for file logs
        }

        return $event;
    }

    /**
     * Get back trace
     * @return array|null
     */
    private function getTrace()
    {
        $trace = array();

        if (PHP_VERSION_ID >= 50400) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        } else if (PHP_VERSION_ID >= 50306) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        } else {
            $trace = debug_backtrace();
        }
        if (empty($trace)) {
            return '';
        }
        array_shift($trace); // ignore $this->getTrace();
        array_shift($trace); // ignore $this->process()
        $i = 0;
        $returnArray = array();
        while (isset($trace[$i]['class']) && false === strpos($trace[$i]['class'], 'Zend\\Log')
            && false === strpos($trace[$i]['class'], 'DhErrorLogging')) {
            $i++;
            $returnArray[] = array(
                'file'     => isset($trace[$i]['file'])   ? $trace[$i]['file']   : null,
                'line'     => isset($trace[$i]['line'])   ? $trace[$i]['line']   : null,
                'class'    => isset($trace[$i]['class'])    ? $trace[$i]['class']    : null,
                'function' => isset($trace[$i]['function']) ? $trace[$i]['function'] : null,
            );
        }

        return $returnArray;
    }

}
