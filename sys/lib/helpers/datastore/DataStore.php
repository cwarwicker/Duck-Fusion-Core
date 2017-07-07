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
 * DataStore
 * 
 * This abstract class contains all the generic methods for working with a data store, and should be extended into different classes for each type of data store
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

namespace DF\Helpers\datastore;

use DF\Helpers\datastore\exception\DataStoreException;

abstract class DataStore {
    
    /**
     * The working directory
     * @var type 
     */
    protected $dir;
    
    /**
     * Temporary variable to override the makeDir method, when checking if a path is ok()
     * @var type 
     */
    protected $tmpNoMake = false;
    
    /**
     * If you want to lock to finding files only inside this specific directory and no sub directories, set this to true with lock()
     * @var bool
     */
    protected $locked = false;
    
    /**
     * Default file permissions to use if we create a new file
     * @var type 
     */
    protected $chmod = 0775;
    
    /**
     * If attempting to copy/move a file into a directory which does not exist, force the creation of the directory
     * @var type 
     */
    protected $forceCreate = false;
    
    
    /**
     * Construct the object and attempt to connect to the data source
     * @param type $params
     * @throws DataStoreException
     */
    public function __construct($params) {
        
        if (!$this->connect($params)){
            DataStoreException::connectionFailed();
        }
               
    }
    
    public function __destruct() {
        $this->disconnect();
    }
    
    public function chmod($chmod){
        $this->chmod = $chmod;
        return $this;
    }
    
    public function lock(){
        $this->locked = true;
        return $this;
    }
    
    public function unlock(){
        $this->locked = false;
        return $this;
    }
    
    public function forceCreate($val = true){
        $this->forceCreate = $val;
        return $this;
    }
    
    abstract protected function connect($params);
    abstract protected function disconnect();
    abstract protected function makeDir($path);
    abstract public function change($params);
    abstract public function find($path);
    abstract public function listAll();
    abstract public function ok($file);
    abstract public function touch($path);
    
}
