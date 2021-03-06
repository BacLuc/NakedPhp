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
 * @package    NakedPhp_Stubs
 */

namespace NakedPhp\Stubs;

/**
 * @Singleton
 */
class UserFactory
{
    public function createUser()
    {
        return new User();
    }

    /**
     * @Hidden
     */
    public function mySkippedMethod()
    {
    }

    public function __toString()
    {
        return 'UserFactory';
    }
}
