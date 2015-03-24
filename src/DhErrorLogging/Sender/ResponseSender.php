<?php
/**
 * Response Sender
 *
 * @author     David Havl <contact@davidhavl.com>
 * @copyright  Copyright 2004-2015 David Havl
 * @license    MIT , http://davidhavl.com/license/MIT
 * @link       http://davidhavl.com
 */

namespace DhErrorLogging\Sender;

use Zend\Console\Console;
use Zend\Console\Response as ConsoleResponse;
use Zend\Json\Json;
use Zend\Stdlib\RequestInterface;
use Zend\Stdlib\ResponseInterface;
use Zend\Mvc\ResponseSender\SendResponseEvent;
use DhErrorLogging\Options\ModuleOptions;


class ResponseSender implements ResponseSenderInterface
{

    // TODO: this could be further separated into smaller units/classes (strategies, renderers,...)


    protected $request = null;
    protected $response = null;
    protected $options = null;

    public function __construct(RequestInterface $request, ResponseInterface $response, ModuleOptions $options)
    {
        $this->request = $request;
        $this->response = $response;
        $this->options = $options;
    }



    /**
     * Processes and send response to browser
     *
     * @param SendResponseEvent $event event data
     */
    public function send(SendResponseEvent $event)
    {


        // Console //

        if ($this->response instanceof ConsoleResponse) {
            $templateContent = $this->getTemplateContent('console');
            // inject details
            $templateContent = $this->injectTemplateContent($templateContent, $event->getParams());
            $this->response->setContent($templateContent);
            $this->response->setErrorLevel(1);

            return $this->sendResponse($this->response);
        }


        // HTTP //

        $this->response->setStatusCode(500);

        $requestHeaders = $this->request->getHeaders();
        if ($requestHeaders->has('accept')) {

            $accept = $requestHeaders->get('Accept');
            $responseHeaders = $this->response->getHeaders();
            $responseHeaders->clearHeaders();

            if ($accept->match('text/html') !== false) { // html

                // get template content
                $templateContent = $this->getTemplateContent($event->getParam('type'));
                // inject details
                $templateContent = $this->injectTemplateContent($templateContent, $event->getParams());
                $contentType = 'text/html; charset=utf-8';
                $responseHeaders->addHeaderLine('content-type', $contentType);
                $this->response->setContent($templateContent);

            } else if ($accept->match('application/json') !== false
                || $accept->match('application/hal+json') !== false) { // json

                // return api problem structured json
                $contentType = 'application/problem+json; charset=utf-8';
                $responseHeaders->addHeaderLine('content-type', $contentType);
                // get template
                $templateContent = $this->getTemplateContent('json');
                // inject details
                $templateContent = $this->injectTemplateContent($templateContent, $event->getParams());
                $this->response->setContent($templateContent);

            }
        }

        $this->sendResponse($this->response);

    }


    /**
     * Send response to the browser
     * @param $response
     */
    public function sendResponse($response)
    {
        // clean any previous output from buffer. Not effective in some cases
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // console //

        if ($response instanceof ConsoleResponse) {
            echo $response->getContent();
            $errorLevel = (int) $response->getErrorLevel();
            exit($errorLevel);
        }


        // http //

        // send headers
        foreach ($response->getHeaders() as $header) {
            header($header->toString());
        }

        // send status
        $status = $response->renderStatusLine();
        header($status);

        // send content
        echo $response->getContent();
        exit(1);
    }



    /**
     * return module options
     * @return ModuleOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * set module options
     * @param ModuleOptions $options
     */
    public function setOptions(ModuleOptions $options)
    {
        $this->options = $options;
    }


    /**
     * Get content of template
     * @param string $type type of the template
     * @return string
     */
    public function getTemplateContent($type)
    {
        $type = strtolower($type);
        $templateContent = "An error has occured.";
        if ($this->getOptions()->getTemplate($type)
            && file_exists($this->getOptions()->getTemplate($type))) {

            $templatePath = $this->getOptions()->getTemplate($type);
            // read content of file
            $templateContent = file_get_contents($templatePath);
        }

        // TODO: translations?
        return $templateContent;
    }

    /**
     * replace placeholders in template with actual values
     * @param string $content
     * @param array $params
     * @return string
     */
    public function injectTemplateContent($content, $params)
    {
        return str_replace(
            array(
                '%__ERROR_TYPE__%',
                '%__ERROR_REFERENCE__%',
                '%__ERROR_MESSAGE__%',
                '%__ERROR_FILE__%',
                '%__ERROR_LINE__%'
            ),
            array_map('addslashes',array(
                $params['type'],
                $params['reference'],
                $params['message'],
                $params['file'],
                $params['line']
            )),
            $content
        );
    }

}
