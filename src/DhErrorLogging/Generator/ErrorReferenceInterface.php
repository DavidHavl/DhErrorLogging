<?php
/**
 * Error reference generator interface.
 *
 * @license http://DavidHavl.com/license/MIT
 * @link http://DavidHavl.com
 * @author: davidhavl
 */

namespace DhErrorLogging\Generator;

interface ErrorReferenceInterface
{
    /**
     * generate random string
     *
     * @return string
     */
    public function generate();
} 