<?php
/**
 * Exception filter interface.
 *
 * @license http://davidhavl.com/license/MIT
 * @link http://davidhavl.com
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