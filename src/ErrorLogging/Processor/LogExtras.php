<?php


namespace ErrorLogging\Processor;

use Zend\Log\Processor\ProcessorInterface;
use Zend\Http\PhpEnvironment\RemoteAddress;
use Zend\Http\PhpEnvironment\Request;

class LogExtras implements ProcessorInterface
{
    protected $request = null;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }


    /**
     * Adds IP and uri to the event extras
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

        // check if there is a trace array and format it as simple string
        // this is necessary mainly because Logger::registerExceptionHandler sets trace as array rather than string
        if (!empty($event['extra']['trace']) && is_array($event['extra']['trace'])) {
            $traceString = '';
            foreach ($event['extra']['trace'] as $index=>$trace) {
                $traceString .= '#' . $index
                    . (isset($trace['file'])?$trace['file']:'')
                    . "(" . (isset($trace['line'])?$trace['line']:'') . "): "
                    . (isset($trace['class'])?$trace['class']:'')
                    . (isset($trace['type'])?$trace['type']:' ')
                    . (isset($trace['function'])?$trace['function']:'')
                    // . "Args  : " . print_r($trace['args'], true) . "\n" // Careful, this can take a lot of memory.
                    ;
            }
            $event['extra']['trace'] = "\n[Trace]\n" . $traceString . "\n";
        }

        return $event;
    }

}
