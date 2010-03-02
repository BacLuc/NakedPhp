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
use NakedPhp\ProgModel\OneToOneAssociation;
use NakedPhp\ProgModel\PhpAction;
use NakedPhp\ProgModel\PhpActionParameter;

class ProgModelFactory implements MetaModelFactory
{
    protected $_reflector;
    protected $_specificationLoader;

    public function __construct(MethodsReflector $reflector)
    {
        $this->_reflector = $reflector;
    }

    public function initSpecificationLoader(SpecificationLoader $loader)
    {
        $this->_specificationLoader = $loader;
    }

    /**
     * {@inheritdoc}
     */
    public function createAssociation(\ReflectionMethod $getter)
    {
        $identifier = $this->_reflector->getIdentifierForAssociation($getter);
        $type = $this->_reflector->getReturnType($getter);
        if ($type === null) {
            $type = 'string';
        }
        $spec = $this->_specificationLoader->loadSpecification($type);
        return new OneToOneAssociation($spec, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function createAction(\ReflectionMethod $method)
    {
        $identifier = $this->_reflector->getIdentifierForAction($method);
        $oldParams = $this->_reflector->getParameters($method);
        $params = array();
        foreach ($oldParams as $idParam => $param) {
            $type = $param['type'] === null ? 'string' : $param['type'];
            $paramSpec = $this->_specificationLoader->loadSpecification($type);
            $params[$idParam] = new PhpActionParameter($paramSpec, $idParam);
        };
        $returnType = $this->_reflector->getReturnType($method);
        $type = $returnType === null ? 'string' : $returnType;
        $returnSpec = $this->_specificationLoader->loadSpecification($type);
        return new PhpAction($identifier, $params, $returnSpec);
    }
}
