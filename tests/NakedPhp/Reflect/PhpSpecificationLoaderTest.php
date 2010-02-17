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
use NakedPhp\MetaModel\NakedObjectSpecification;
use NakedPhp\ProgModel\PhpSpecification;

class PhpSpecificationLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testObtainsPhpSpecificationObjectsFromTheFactory()
    {
        $specFactory = new DummySpecificationFactory();
        $introspectorFactory = new
        DummyIntrospectorFactory($this->_getIntrospector());
        $specLoader = new PhpSpecificationLoader($specFactory, $introspectorFactory);
        $specLoader->init();
        $specification = $specLoader->loadSpecification('My_Model_EntityA');
        $this->assertTrue($specification instanceof NakedObjectSpecification);
        $this->assertEquals('My_Model_EntityA', (string) $specification);
    }

    private function _getIntrospector()
    {
        $introspector = $this->getMock('NakedPhp\Reflect\PhpIntrospector');
        $introspector->expects($this->exactly(2))
                     ->method('introspectClass');
        $introspector->expects($this->exactly(2))
                     ->method('introspectAssociations');
        $introspector->expects($this->exactly(2))
                     ->method('introspectActions');
        return $introspector;
    }
}

class DummySpecificationFactory implements SpecificationFactory
{
    public function getSpecifications()
    {
        return array(
            'My_Model_EntityA' => new PhpSpecification('My_Model_EntityA'),
            'My_Model_EntityB' => new PhpSpecification('My_Model_EntityB')
        );
    }
}

class DummyIntrospectorFactory implements IntrospectorFactory
{
    protected $_introspector;

    public function __construct(PhpIntrospector $introspector)
    {
        $this->_introspector = $introspector;
    }

    public function getIntrospector(NakedObjectSpecification $spec)
    {
        return $this->_introspector;
    }
}
