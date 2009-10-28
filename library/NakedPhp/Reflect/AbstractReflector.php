<?php
/**
 * Naked Php is a framework that implements the Naked Objects pattern.
 * @copyright Copyright (C) 2009  Giorgio Sironi
 * @license http://www.gnu.org/licenses/lgpl-2.1.txt 
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * @category   NakedPhp
 * @package    NakedPhp_Reflect
 */

namespace NakedPhp\Reflect;

abstract class AbstractReflector
{
    protected $_parser;

    public function __construct(DocblockParser $parser = null)
    {
        $this->_parser = $parser;
    }
    /**
     * @param string $docblock  documentation block of a method or property
     * @return boolean
     */
    protected function _isHidden($docblock)
    {
        return $this->_parser->contains('Hidden', $docblock);
    }

    protected function _isMagic($methodName)
    {
        return substr($methodName, 0, 2) == '__';
    }
}
