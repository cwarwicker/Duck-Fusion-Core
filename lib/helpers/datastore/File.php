<?php
/*

    This file is part of the DuckFusion Framework.

    This is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    DuckFusion Framework is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with DuckFusion Framework.  If not, see <http://www.gnu.org/licenses/>.

*/

/**
 *
 * File
 * 
 * This abstract class contains all the generic methods for working with files, and should be extended into different classes for each type of data store
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

namespace DF\Helpers\datastore;

abstract class File {
    
    /**
     * Path to the file
     * @var type 
     */
    protected $file;
    
    /**
     * DataStore object we found this file in
     * @var type 
     */
    protected $store;
    
    /**
     * Construct the File object
     * @param type $file
     */
    public function __construct($file) {
        $this->file = $file;
    }
    
    abstract public function getSize();    
    abstract public function read();
    abstract public function write($content);
    abstract public function copy($target);    
    abstract public function move($target);     
    abstract public function delete();
    abstract public function exists(); 
    abstract public function readable();
    abstract public function writable();
    abstract public function checksum($method = 'md5');


    /**
     * Returns the full path to the file
     * @return string Returns the full path to the file
     */
    public function getFullPath(){
        return $this->file;
    }
    
    /**
     * Get the actual file name from the full path
     * @return string Returns the file name
     */
    public function getFileName(){
        return basename($this->file);
    }
    
    /**
     * Get the directory the file is in
     * @return string Returns the directory name
     */
    public function getDirectoryName(){
        return dirname($this->file);
    }
    
    /**
     * Get the file extension from a file name
     * @param type $filename
     * @return type
     */
    public function getExtension()
    {
                        
        $exts = explode(".", $this->getFileName());
        
        // No extension
        if (count($exts) == 1){
            return '';
        }
        
        return $exts[count($exts) - 1];
        
    }
    
    /**
     * Set the DataStore reference
     * @param type $store
     * @return $this
     */
    public function setStore($store){
        $this->store = $store;
        return $this;
    }
    
    /**
     * Get the DataStore reference
     * @return type
     */
    public function getStore(){
        return $this->store;
    }
    
}
