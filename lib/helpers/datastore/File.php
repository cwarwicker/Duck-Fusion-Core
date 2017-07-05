<?php

namespace DF\Helpers\datastore;


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
