<?php

namespace DF\Helpers\datastore\stores;

use DF\Helpers\datastore\files\LocalFile;

/**
 * Description of Directory
 *
 * @author Conn
 */
class LocalDirectory extends \DF\Helpers\datastore\DataStore {
        
    
    
    /**
     * Construct the LocalDirectory DataStore object
     * @param type $params
     */
    public function __construct($params) {
        parent::__construct($params);
        $this->dir = $params;
    }
    
    /**
     * Get the current working directory
     * @return type
     */
    public function getDir(){
        return $this->dir;
    }
    
    /**
     * Since this is a local directory and not anything we need to actually connect to, just make sure we can read this directory
     * @param type $params
     * @return type
     */
    protected function connect($params) {
        return (is_dir($params));
    }

    /**
     * Blank disconnect method
     */
    protected function disconnect() {}

    /**
     * Change the directory we are looking at
     * @param type $params
     */
    public function change($params) {
        $this->dir = $params;
    }
    
    /**
     * Find a file and return its LocalFile object
     * @param type $path
     */
    public function find($path) {
        
        $this->tmpNoMake = true;
        
        // Check the path is ok
        $filepath = $this->ok($path);
        if (!$filepath){
            return false;
        }
        
        $this->tmpNoMake = false;
        
        $file = new LocalFile($filepath);
        $file->setStore($this);
        return ($file->exists()) ? $file : false;
        
    }

    /**
     * Find or create a file
     * @param type $path
     * @return type
     */
    public function touch($path) {
        
        // If it already exists, just return it
        $file = $this->find($path);
        if ($file){
            return $file;
        } else {
            return $this->make($path);
        }
        
    }
    
    /**
     * Create a file
     * @param type $path
     * @return boolean
     */
    protected function make($path){
        
        // Check the path is ok
        $filepath = $this->ok($path);
        if (!$filepath){
            return false;
        }
                
        // Now try and create the file
        $handle = fopen($filepath, 'w');
        if ($handle){
            fclose($handle);
            chmod($filepath, $this->chmod);
        }
        
        return $this->find($path);
        
    }
    
    /**
     * Create a directory
     * @param type $path
     * @return type
     */
    protected function makeDir($path){
        
        if ($this->tmpNoMake){
            return false;
        }
        
        return mkdir($path, $this->chmod, true);
        
    }
        
     /**
     * Check that a given file path is inside the path we specified for our DataStore
     * This prevents us starting in, say, /data/ and then trying to delete or copy a file from outside this directory, by doing ../some/other/dir
     * @param type $file
     * @return type
     */
    public function ok($file){
        
        $file = $this->dir . df_DS . $file;
        $realpath = realpath($file);
        
        $dirpath = ($realpath) ? dirname($realpath) : dirname($file);
        $chkpath = ($realpath) ? $realpath : $dirpath;
                                
        // Can't always use realpath here as the file doesn't exist, so will just have to compare the paths and return false if any double dots are found
        if (strpos($chkpath, $this->dir) !== 0 || strpos($chkpath, '..') !== false){
            return false;
        } 
        
        // If we are locked to this specific directory, the directories must match exactly, not be a sub directory
        if ($this->locked && $dirpath !== $this->dir ){
            return false;
        }
        
        // If the directory itself does not exist, return false, unless we have forceCreate enabled, then we can try and create it first
        if (!is_dir($dirpath) && !$this->makeDir($dirpath)){
            return false;
        }
        
        return $file;
        
    }

    /**
     * Get a recursive array of all the directories and files within the working directory
     * @return type
     */
    public function listAll() {
    
        $directory = new \RecursiveDirectoryIterator($this->dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);
        $files = array();
        
        foreach($iterator as $path => $dir){
            $files[] = $path;
        }
        
        return $files;
        
    }

}
