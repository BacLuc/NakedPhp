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
 * @package    NakedPhp_Form
 */

namespace NakedPhp\Form;
use NakedPhp\ProgModel\NakedObjectMethod;

class MethodFormBuilder
{
    public function createForm(NakedObjectMethod $method)
    {
        $form = new \Zend_Form();
        foreach ($method->getParameters() as $name => $param) {
            $input = new \Zend_Form_Element_Text($param->getName());
            $input->setLabel($name);
            $form->addElement($input);
        }
        $form->addElement(new \Zend_Form_Element_Submit('nakedphp_submit', array(
                            'label' => 'Call',
                            'ignore' => 'true'
                         )));
        return $form;
    }
}
