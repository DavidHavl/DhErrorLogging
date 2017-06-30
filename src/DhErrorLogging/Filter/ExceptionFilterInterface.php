<?php
/**
 * Exception filter interface.
 *
 * @license http://DavidHavl.com/license/MIT
 * @link http://DavidHavl.com
 * @author: davidhavl
 */

namespace DhErrorLogging\Filter;

interface ExceptionFilterInterface
{
    /**
     * Check if the given exception is allowed
     *
     * @return bool
     */
    public function isAllowed($exception);
} 