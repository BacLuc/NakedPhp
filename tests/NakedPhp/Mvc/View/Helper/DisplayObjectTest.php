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
 * @package    NakedPhp_Mvc
 */

namespace NakedPhp\Mvc\View\Helper;
use NakedPhp\Stubs\NakedObjectStub;
use NakedPhp\ProgModel\OneToOneAssociation;
use NakedPhp\ProgModel\Facet\HiddenMethod;

class DisplayObjectTest extends \PHPUnit_Framework_TestCase
{
    private $_helper;
    private $_object;

    public function setUp()
    {
        $this->_object = new NakedObjectStub($this);
        $this->_object->setState(array(
            'firstName' => 'Giorgio',
            'lastName' => 'Sironi'
        ));
        $this->_object->setField('firstName', new OneToOneAssociation('string', 'firstName'));
        $this->_object->setField('lastName', new OneToOneAssociation('string', 'lastName'));
        $this->_helper = new DisplayObject();
    }

    public function testProducesHtmlTable()
    {
        $this->_object->setClassName('DummyClass');
        $result = $this->_helper->displayObject($this->_object);
        $this->assertQuery($result, 'table.nakedphp_entity.DummyClass');
        $this->assertQuery($result, 'table tr');

        $this->assertQueryContentContains($result, 'table tr td', 'firstName');
        $this->assertQueryContentContains($result, 'table tr td', 'Giorgio');
        $this->assertQueryContentContains($result, 'table tr td', 'lastName');
        $this->assertQueryContentContains($result, 'table tr td', 'Sironi');
    }

    public function testHidesFieldsProgrammatically()
    {
        $this->_object->getAssociation('firstName')->addFacet(new HiddenMethod('hideFirstName'));
        $result = $this->_helper->displayObject($this->_object);

        $this->assertQueryContentNotContains($result, 'table tr td', 'firstName');
    }

    public function hideFirstName()
    {
        return true;
    }

    public function testDisplaysACollectionAsATable()
    {
        $second = clone($this->_object);
        $second->setState(array('firstName' => 'Isaac', 'lastName' => 'Asimov'));
        $collection = new \ArrayIterator(array($this->_object, $second));

        $result = $this->_helper->displayObject($collection);

        $this->assertQuery($result, 'table');
        $this->assertQueryContentContains($result, 'table tr td', 'Isaac');
    }

    /**
     * Assert against DOM selection
     * 
     * @param  string $path CSS selector path
     * @param  string $message
     * @return void
     */
    public function assertQuery($content, $path, $message = '')
    {
        $constraint = new \Zend_Test_PHPUnit_Constraint_DomQuery($path);
        $this->assertTrue($constraint->evaluate($content, __FUNCTION__));
    }

    /**
     * Assert against DOM selection; node should contain content
     * 
     * @param  string $path CSS selector path
     * @param  string $match content that should be contained in matched nodes
     * @param  string $message
     * @return void
     */
    public function assertQueryContentContains($content, $path, $match, $message = '')
    {
        $constraint = new \Zend_Test_PHPUnit_Constraint_DomQuery($path);
        $this->assertTrue($constraint->evaluate($content, __FUNCTION__, $match));
    }

    public function assertQueryContentNotContains($content, $path, $match, $message = '')
    {
        $constraint = new \Zend_Test_PHPUnit_Constraint_DomQuery($path);
        $this->assertTrue($constraint->evaluate($content, __FUNCTION__, $match));
    }
}
