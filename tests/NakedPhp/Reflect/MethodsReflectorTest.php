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
use NakedPhp\ProgModel\OneToOneAssociation;

class MethodsReflectorTest extends \PHPUnit_Framework_TestCase
{
    private $_parserMock;
    private $_reflector;

    public function setUp()
    {
        $this->_parserMock = $this->getMock('NakedPhp\Reflect\DocblockParser', array('parse', 'contains'));
        $this->_reflector = new MethodsReflector($this->_parserMock);
        $this->_reflectionClass = new \ReflectionClass('NakedPhp\Reflect\DummyReflectedClass');
    }

    private function setMockAnnotations($annotations = null)
    {
        if (is_null($annotations)) {
            $annotations = array(
               array(
                   'annotation' => 'return',
                   'type' => 'integer',
                   'description' => 'The role of the user'
               )
           );
        }
        $this->_parserMock->expects($this->any())
                   ->method('parse')
                   ->will($this->returnValue($annotations));
    }

    public function testFindsIdentifierForAction()
    {
        $method = $this->_reflectionClass->getMethod('myMethod');
        $identifier = $this->_reflector->getIdentifierForAction($method);
        $this->assertEquals('myMethod', $identifier);
    }

    public function testFindsIdentifierForAssociation()
    {
        $getter = $this->_reflectionClass->getMethod('getMyField');
        $identifier = $this->_reflector->getIdentifierForAssociation($getter);
        $this->assertEquals('myField', $identifier);
    }

    public function testFindsMethodReturnType()
    {
        $this->setMockAnnotations(array(
            array(
                'annotation' => 'return',
                'type' => 'integer',
                'description' => 'The role of the user'
            )
        ));

        $method = $this->_reflectionClass->getMethod('getMyField');
        $type = $this->_reflector->getReturnType($method);
        $this->assertEquals('integer', $type);
    }

    public function testSetsMethodReturnTypeAsNullIfNoAnnotationCanBeFound()
    {
        $this->setMockAnnotations(array());

        $method = $this->_reflectionClass->getMethod('getMyField');
        $type = $this->_reflector->getReturnType($method);
        $this->assertNull($type);
    }

    public function testFindsParametersTypeAndIdentifiers()
    {
        $this->setMockAnnotations(array(
            array(
                'annotation' => 'param',
                'type' => 'integer',
                'name' => 'myParameter',
                'description' => 'My useful parameter.'
            )
        ));

        $method = $this->_reflectionClass->getMethod('myMethod');
        $params = $this->_reflector->getParameters($method);
        $this->assertEquals(array(
                                'myParameter' => array(
                                    'type' => 'integer',
                                    'description' => 'My useful parameter.'
                                )
                            ),
                            $params);
    }
}

class DummyReflectedClass
{
    /**
     * Docblocks will be mocked.
     */
    public function myMethod() {}
    public function getMyField() {}
}
