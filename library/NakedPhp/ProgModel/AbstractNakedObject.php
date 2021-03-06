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
use NakedPhp\MetaModel\NakedObject;
use NakedPhp\MetaModel\Facet;

/**
 * Decorator for a domain object. Wraps the object itself and its specification.
 * Here are centralized all delegations to the NakedObjectSpecification object to avoid duplication.
 */
abstract class AbstractNakedObject implements NakedObject
{
    /**
     * @var NakedObjectSpecification
     */
    protected $_class;

    /**
     * {@inheritdoc}
     * Convenience method.
     */
    public function getClassName()
    {
        return $this->_class->getClassName();
    }

    /**
     * {@inheritdoc}
     * Convenience method.
     */
    public function isService()
    {
        return $this->_class->isService();
    }

    /**
     * {@inheritdoc}
     * Convenience method.
     */
    public function getObjectActions()
    {
        return $this->_class->getObjectActions(); 
    }

    /**
     * {@inheritdoc}
     * Convenience method.
     */
    public function getObjectAction($methodName)
    {
        $methods = $this->getObjectActions();
        return $methods[$methodName];
    }

    /**
     * {@inheritdoc}
     * Convenience method.
     */
    public function hasObjectAction($methodName)
    {
        $methods = $this->getObjectActions();
        return isset($methods[$methodName]);
    }

    /**
     * {@inheritdoc}
     * Convenience method.
     */
    public function getAssociations()
    {
        return $this->_class->getAssociations();
    }

    /**
     * {@inheritdoc}
     * Convenience method.
     */
    public function getAssociation($name)
    {
        return $this->_class->getAssociation($name);
    }
    
    /**
     * {@inheritdoc}
     * Not allowed.
     */
    public function addFacet(Facet $facet)
    {
        throw new \Exception('It is not possible to add a Facet to an object. Access the NakedObjectSpecification instance instead.');
    }

    /**
     * {@inheritdoc}
     * Proxies to the NakedObjectSpecification instance.
     */
    public function getFacet($type)
    {
        return $this->_class->getFacet($type);
    }

    /**
     * {@inheritdoc}
     * Proxies to the NakedObjectSpecification instance.
     */
    public function getFacets($type)
    {
        return $this->_class->getFacets($type);
    }

}
