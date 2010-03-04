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
 * @category   Example
 * @package    Example_Model
 */

/**
 * @Service
 */
class Example_Model_PlaceFactory
{
    /**
     * @return Example_Model_Place
     */
    public function createPlace()
    {
        return new Example_Model_Place();
    }

    /**
     * @param string $name  name of the category (disco, pub...)
     * @return Example_Model_PlaceCategory
     */
    public function createPlaceCategory($name)
    {
        return new Example_Model_PlaceCategory($name);
    }

    /**
     * @param string $name  the city name
     * @return Example_Model_City
     */
    public function createCity($name)
    {
        return new Example_Model_City($name);
    }

    /**
     * @return array
     * @TypeOf(Example_Model_City)
     */
    public function createSomeCities()
    {
        return array(
            new Example_Model_City('New York'),
            new Example_Model_City('Moscow'),
            new Example_Model_City('Madrid'),
            new Example_Model_City('London')
        );
    }

    public function __toString()
    {
        return 'PlaceFactory';
    }
}

