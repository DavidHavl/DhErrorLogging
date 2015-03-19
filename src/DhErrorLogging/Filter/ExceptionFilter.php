<?php
/**
 * Exception filter
 *
 * @license http://davidhavl.com/license/MIT
 * @link http://davidhavl.com
 * @author: davidhavl
 */
namespace DhErrorLogging\Filter;


class ExceptionFilter implements ExceptionFilterInterface
{
    private $blacklist = array();

    public function __construct(array $blacklist)
    {
        if (!empty($blacklist)) {
            $this->blacklist = $blacklist;
        }
    }

    /**
     * Check if the given exception is allowed
     *
     * @param \Exception $exception
     * @return bool
     */
    public function isAllowed($exception)
    {
        if (empty($exception) || !$exception instanceof \Exception) {
            return true;
        }

        if (!empty($this->blacklist)) {
            foreach ($this->blacklist as $excType) {
                if (is_a($exception, $excType, true)) {
                    return false;
                }
            }
        }

        return true;
    }
}