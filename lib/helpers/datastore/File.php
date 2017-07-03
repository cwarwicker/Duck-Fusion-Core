<?php

namespace DF\Helpers\datastore;

use DF\Helpers\datastore\exception\FileException;

/**
 * Description of File
 *
 * @author Conn
 */
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
    
    abstract public function read();
    abstract public function write($content);
    abstract public function copy($target);    
    abstract public function move($target);     
    abstract public function delete();
    abstract public function exists();
    abstract public function readable();
    abstract public function writable();
    
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
     * Get the permissions of the file
     * @return int
     */
    public function getPermissions(){
        return fileperms($this->file);
    }
    
    /**
     * Get the owner of the file
     * @return int Owner ID or FALSE
     */
    public function getOwner(){
        return fileowner($this->file);
    }
    
    /**
     * Returns the group of the file
     * @return int Group ID or FALSE
     */
    public function getGroup(){
        return filegroup($this->file);
    }
    
    /**
     * Get the modified date
     * @return \DateTime
     */
    public function getModified(){
        $timestamp = filemtime($this->file);
        $time = new \DateTime();
        $time->setTimestamp($timestamp);
        return $time;
    }
    
    /**
     * Gets the created time.
     *
     * @return DateTime
     */
    public function getCreated() {
        $timestamp = filectime($this->file);
        $time = new \DateTime();
        $time->setTimestamp($timestamp);
        return $time;
    }
    
    /**
     * Gets last access time.
     * 
     * @return DateTime
     */
    public function getLastAccessed() {
        $timestamp = fileatime($this->file);
        $time = new \DateTime();
        $time->setTimestamp($timestamp);
        return $time;
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
