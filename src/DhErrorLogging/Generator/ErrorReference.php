<?php
/**
 * Error reference generator.
 *
 * @license http://davidhavl.com/license/MIT
 * @link http://davidhavl.com
 * @author: davidhavl
 */

namespace DhErrorLogging\Generator;


class ErrorReference implements ErrorReferenceInterface
{
    public function generate()
    {
        $chars = md5(uniqid('', true));
        return substr($chars, 2, 2) . substr($chars, 12, 2) . substr($chars, 26, 2);
    }
} 