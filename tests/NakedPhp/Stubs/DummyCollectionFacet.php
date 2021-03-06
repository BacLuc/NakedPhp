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
 * @package    NakedPhp_Stubs
 */

namespace NakedPhp\Stubs;
use NakedPhp\MetaModel\NakedObject;
use NakedPhp\MetaModel\Facet\Collection;

class DummyCollectionFacet implements Collection
{
    protected $_content;

    /**
     * @param array $content    NakedObject instances
     */
    public function __construct(array $content = array())
    {
        $this->_content = $content;
    }

    public function facetType()
    {
        return 'Collection';
    }

    public function toArray(NakedObject $no)
    {
        return $this->_content;
    }

    public function iterator(NakedObject $no)
    {
        return new \ArrayIterator($this->_content);
    }
}
