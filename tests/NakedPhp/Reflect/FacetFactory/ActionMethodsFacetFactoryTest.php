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

namespace NakedPhp\Reflect\FacetFactory;
use NakedPhp\Reflect\MethodRemover;
use NakedPhp\MetaModel\Facet;
use NakedPhp\MetaModel\NakedObjectAction;
use NakedPhp\MetaModel\NakedObjectFeatureType;
use NakedPhp\ProgModel\PhpAction;
use NakedPhp\Stubs\DummyMethodRemover;
use NakedPhp\Stubs\FacetHolderStub;
use NakedPhp\Stubs\NakedObjectSpecificationStub;

class ActionMethodsFacetFactoryTest extends \PHPUnit_Framework_TestCase
{
    private $_docblockParser;
    private $_facetFactory;
    private $_reflectionClass;

    public function setUp()
    {
        $this->_facetFactory = new ActionMethodsFacetFactory();
        $this->_reflectionClass = new \ReflectionClass('NakedPhp\Reflect\FacetFactory\SomeRandomClass');
    }

    public function testIsAppropriateForSomeFeatureType()
    {
        $this->assertEquals(array(NakedObjectFeatureType::ACTION),
                            $this->_facetFactory->getFeatureTypes());
    }

    public function testAddsInvocationFacetToOrdinaryMethods()
    {
        $facetHolder = $this->_processMethod('doSomething');

        $this->assertNotNull($facetHolder->getFacet('Action\Invocation'));
    }

    public function testAddsHiddenFacetIfHideMethodIsPresent()
    {
        $facetHolder = $this->_processMethod('doSomething');

        $this->assertNotNull($facetHolder->getFacet('Hidden'));
    }

    /**
     * @param string $name  method name on SomeRandomClass
     * @return FacetHolder
     */
    private function _processMethod($name)
    {
        $method = $this->_reflectionClass->getMethod($name);
        $methodRemover = new DummyMethodRemover();
        $facetHolder = new PhpAction();
        $this->_facetFactory->processMethod($this->_reflectionClass, $method, $methodRemover, $facetHolder);
        return $facetHolder;
    }
}

class SomeRandomClass
{
    public function doSomething() {}
    public function hideDoSomething() {}
}
