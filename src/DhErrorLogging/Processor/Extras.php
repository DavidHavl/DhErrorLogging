<?php


namespace DhErrorLogging\Processor;

use Zend\Log\Processor\ProcessorInterface;
use Zend\Http\PhpEnvironment\RemoteAddress;
use Zend\Http\PhpEnvironment\Request;

class Extras implements ProcessorInterface
{
    protected $request = null;

    public function __construct(Request $request)
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
        // get request uri and IP address and add it to the extras of the logger
        $remoteAddress = new RemoteAddress();
        $extras = array(
            'uri' => $this->request->getUriString(),
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

        // check if there is a trace as an array and format it as simple string
        // this is necessary mainly because Logger::registerExceptionHandler and our getTrace method sets trace as array rather than string.
        if (!empty($event['extra']['trace']) && is_array($event['extra']['trace'])) {
            $traceString = '';
            foreach ($event['extra']['trace'] as $index=>$trace) {
                // do not log the logger itself
                $traceString .= '#' . $index
                    . (isset($trace['file'])?$trace['file']:'')
                    . "(" . (isset($trace['line'])?$trace['line']:'') . "): "
                    . (isset($trace['class'])?$trace['class']:'')
                    . (isset($trace['type'])?$trace['type']:' ')
                    . (isset($trace['function'])?$trace['function']:'')
                    ;
            }
            $event['extra']['trace'] = $traceString . "\n";
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
            return null;
        }

        array_shift($trace); // ignore $this->getTrace();
        array_shift($trace); // ignore $this->process()
        $i = 0;
        $returnArray = array();
        while (isset($trace[$i]['class']) && false !== strpos($trace[$i]['class'], 'Zend\\Log')) {
            $i++;
            $returnArray[] = array(
                'file'     => isset($trace[$i-1]['file'])   ? $trace[$i-1]['file']   : null,
                'line'     => isset($trace[$i-1]['line'])   ? $trace[$i-1]['line']   : null,
                'class'    => isset($trace[$i]['class'])    ? $trace[$i]['class']    : null,
                'function' => isset($trace[$i]['function']) ? $trace[$i]['function'] : null,
            );
        }

        return $returnArray;
    }

}
