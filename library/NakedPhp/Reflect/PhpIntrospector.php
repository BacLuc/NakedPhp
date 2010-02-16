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
use NakedPhp\MetaModel\FacetHolder;
use NakedPhp\ProgModel\OneToOneAssociation;
use NakedPhp\ProgModel\PhpAction;
use NakedPhp\ProgModel\PhpActionParameter;
use NakedPhp\ProgModel\PhpSpecification;

/**
 * TODO: factor out creation of ProgModel instances in a Factory
 */
class PhpIntrospector
{
    protected $_specification;
    protected $_facetProcessor;
    protected $_reflectionClass;
    protected $_methodRemover;

    public function __construct(PhpSpecification $specification,
                                FacetProcessor $facetProcessor)
    {
        $this->_specification = $specification;
        $this->_facetProcessor = $facetProcessor;
        // FIX: real work; probably necessary since all methods need reflection objects to work
        $this->_reflectionClass = new \ReflectionClass($this->_specification->getClassName());
        $this->_methods = new \ArrayObject($this->_reflectionClass->getMethods());
        $this->_methodRemover = new ArrayObjectMethodRemover($this->_methods);
    }

    /**
     * Initializes the class-level Facets on $this->_specification.
     * @return void
     */
    public function introspectClass()
    {
        $this->_processClass($this->_specification);

        foreach ($this->_methods as $method) {
            $this->_processMethod($method, $this->_specification);
        }
    }

    /**
     * Initializes the associations list on $this->_specification,
     * with the respective Facets.
     * TODO: type of association? (NakedObjectSpecification) will be factored out
     * All by FacetFactories I suppose. See the list of Facets on NOF documentation.
     * @return void
     */
    public function introspectAssociations()
    {
        $associations = array();
        $this->_accessors = $this->_facetProcessor->removePropertyAccessors($this->_methodRemover);
        foreach ($this->_accessors as $accessor) {
            $association = new OneToOneAssociation();
            $identifier = NameUtils::baseName($accessor->getName());
            $this->_processClass($association);
            $this->_processMethod($accessor, $association);
            $associations[$identifier] = $association;
        }
        $this->_specification->initAssociations($associations);
    }

    /**
     * Initializes the list of actions on $this->_specification, 
     * including the respective facets.
     * @return void
     */
    public function introspectActions()
    {
        $actions = array();
        foreach ($this->_methods as $method) {
            if ($this->_facetProcessor->recognizes($method)) {
                continue;
            }
            $name = $method->getName();
            $action = new PhpAction($name);
            $this->_processClass($action);
            foreach ($this->_methods as $collaboratorCandidate) {
                $this->_processMethod($collaboratorCandidate, $action);
            }
            $actions[$name] = $action;
        }
        $this->_specification->initObjectActions($actions);
    }

    /**
     * Currying of $this->_facetProcessor->processClass.
     */
    protected function _processClass(FacetHolder $facetHolder)
    {
        return $this->_facetProcessor->processClass($this->_reflectionClass,
                                                    $this->_methodRemover,
                                                    $facetHolder);
    }

    /**
     * Currying of $this->_facetProcessor->processMethod.
     */
    protected function _processMethod(\ReflectionMethod $method,
                                      FacetHolder $facetHolder)
    {
        return $this->_facetProcessor->processMethod($this->_reflectionClass,
                                                     $method,
                                                     $this->_methodRemover,
                                                     $facetHolder);
    }

}