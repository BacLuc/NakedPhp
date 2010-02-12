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
 * @package    NakedPhp_ProgModel
 */

namespace NakedPhp\ProgModel;
use NakedPhp\MetaModel\NakedObjectSpecification;

/**
 * Wraps properties and actions about a domain class.
 */
class PhpSpecification extends AbstractFacetHolder implements NakedObjectSpecification
{
    /**
     * @var string
     */
    protected $_className;

    /**
     * @var array of NakedObjectMethod instances
     */
    protected $_methods;

    /**
     * @var array of OneToOneAssociation instances
     */
    protected $_fields;

    /**
     * @return boolean
     */
    protected $_isService;

    /**
     * @param string $className
     * @param array $methods
     * @param array $fields
     */
    public function __construct($className = '', $methods = array(), $fields = array())
    {
        $this->_className = $className;
        $this->_methods = $methods;
        $this->_fields = $fields;
    }

    /**
     * @return array NakedObjectMethod
     */
    public function getObjectActions()
    {
        return $this->_methods;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectAction($name)
    {
        $methods = $this->getObjectActions();
        return $methods[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasObjectAction($name)
    {
        $methods = $this->getObjectActions();
        return isset($methods[$name]);
    }

    /**
     * Allows for one time addition of actions after creation.
     */
    public function initObjectActions(array $actions)
    {
        if ($this->_methods !== null) {
            throw new Exception('Actions of a PhpSpecification cannot be set more than one time.');
        }
        $this->_methods = $actions;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociations()
    {
        return $this->_fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociation($name)
    {
        return $this->_fields[$name];
    }

    /**
     * Allows for one time addition of associations after creation.
     */
    public function initAssociations(array $associations)
    {
        if ($this->_fields !== null) {
            throw new Exception('Associations of a PhpSpecification cannot be set more than one time.');
        }
        $this->_fields = $associations;
    }


    /**
     * @return string   the fully qualified class name
     */
    public function getClassName()
    {
        return $this->_className;
    }

    public function __toString()
    {
        return $this->getClassName();
    }

    /**
     * Marks the class a @Entity one.
     * @see $this->isService() will return false.
     */
    public function markAsEntity()
    {
        $this->_checkIsNotMarked();
        $this->_isService = false;
    }

    /**
     * Marks the class a @Service one.
     * @see $this->isService() will return true.
     */
    public function markAsService()
    {
        $this->_checkIsNotMarked();
        $this->_isService = true;
    }

    protected function _checkIsNotMarked()
    {
        if ($this->_isService !== null) {
            throw new Exception("Attempt to mark class $this->_className, which is already marked.");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isService()
    {
        return $this->_isService;
    }
}
