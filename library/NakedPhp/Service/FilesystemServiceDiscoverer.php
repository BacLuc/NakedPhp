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
use NakedPhp\Reflect\ServiceReflector;

/**
 * This class discover php Service classes in a folder of the filesystem.
 * @package    NakedPhp_Service
 */
class FilesystemServiceDiscoverer implements ServiceDiscoverer
{
    private $_serviceReflector;
    private $_folder;
    private $_prefix;

    public function __construct(ServiceReflector $reflector = null, $folder, $prefix = '')
    {
        $this->_serviceReflector = $reflector;
        $this->_folder = $folder;
        $this->_prefix = $prefix;
    }
    
    public function getList()
    {
        $classes = array();
        foreach (new \DirectoryIterator($this->_folder) as $file) {
            $filename = $file->getFilename();
            if ($this->_getExtension($filename) == '.php') {
                $className = $this->_prefix . $this->_getBaseClassName($filename);
                if ($this->_serviceReflector->isService($className)) {
                    $classes[] = $className;
                }
            }
        }
        return $classes;
    }

    private function _getExtension($filename)
    {
        $point = strrpos($filename, '.');
        return substr($filename, $point);
    }

    private function _getBaseClassName($filename)
    {
        $point = strrpos($filename, '.');
        return substr($filename, 0, $point);
    }
}
