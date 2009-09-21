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
 * @category   Example
 * @package    Example_Model
 */

class Example_Model_Place
{
    private $_name = 'Default Name';

    /**
     * @return string   the name
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param string $name  the name of pub, disco
     * @return Place        this object
     */
    public function edit($name)
    {
        $this->_name = (string) $name;
        return $this;
    }

    public function __toString()
    {
        return (string) $this->_name;
    }
}

