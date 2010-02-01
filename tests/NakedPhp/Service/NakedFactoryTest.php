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
 * @package    NakedPhp_Service
 */

namespace NakedPhp\Service;
use NakedPhp\MetaModel\NakedObject;
use NakedPhp\Stubs\NakedObjectSpecificationStub;

class NakedFactoryTest extends \PHPUnit_Framework_TestCase
{
    private $_entityReflectorMock;
    private $_serviceReflectorMock;
    private $_factory;

    public function setUp()
    {
        $this->_entityReflectorMock = $this->getMock('NakedPhp\Reflect\EntityReflector', array('analyze'));
        $this->_serviceReflectorMock = $this->getMock('NakedPhp\Reflect\ServiceReflector', array('isService', 'analyze'));
        $this->_factory = new NakedFactory($this->_entityReflectorMock, $this->_serviceReflectorMock);
    }

    public function testWrapsAnEntityInANakedObjectInstance()
    {
        $this->_serviceReflectorMock->expects($this->any())
                                    ->method('isService')
                                    ->will($this->returnValue(false));
        $no = $this->_factory->createBare(new \stdClass);
        $this->assertTrue($no instanceof NakedObject);
    }

    public function testGeneratesMetaModelForEntities()
    {
        $this->_serviceReflectorMock->expects($this->any())
                                    ->method('isService')
                                    ->will($this->returnValue(false));
        $class = new NakedObjectSpecificationStub();
        $this->_entityReflectorMock->expects($this->any())
                                    ->method('analyze')
                                    ->will($this->returnValue($class));
        $no = $this->_factory->createBare(new \stdClass);
        $this->assertSame($class, $no->getSpecification());
    }

    public function testGeneratesMetaModelForServices()
    {
        $this->_serviceReflectorMock->expects($this->any())
                                    ->method('isService')
                                    ->will($this->returnValue(true));
        $class = new NakedObjectSpecificationStub();
        $this->_serviceReflectorMock->expects($this->any())
                                    ->method('analyze')
                                    ->will($this->returnValue($class));
        $no = $this->_factory->createBare(new \stdClass);
        $this->assertSame($class, $no->getSpecification());
    }

    public function testDoesNotWrapScalarValues()
    {
        $result = $this->_factory->createBare('scalar result');
        $this->assertEquals('scalar result', $result);
    }
}

