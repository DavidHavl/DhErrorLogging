<?php
/**
 * Error reference generator interface.
 *
 * @license http://davidhavl.com/license/MIT
 * @link http://davidhavl.com
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