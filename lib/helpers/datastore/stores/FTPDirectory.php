<?php

namespace DF\Helpers\datastore\stores;

use DF\Helpers\datastore\files\FTPFile;

class FTPDirectory extends \DF\Helpers\datastore\DataStore {
        
    /**
     * Connection resource
     * @var type 
     */
    public $conn = false;
    
    
    
    /**
     * Connect to the host and attempt to login with the credentials supplied
     * @param type $params
     * @return boolean
     */
    protected function connect($params) {
        
        $host = (isset($params['host'])) ? $params['host'] : false;
        $user = (isset($params['user'])) ? $params['user'] : false;
        $pass = (isset($params['pass'])) ? $params['pass'] : false;
        $port = (isset($params['port'])) ? $params['port'] : 21;
        $dir  = (isset($params['dir']))  ? $params['dir']  : '';
        
        // If no host, just stop
        if (!$host){
            return false;
        }
        
        // Try to connect to the host
        $conn = ftp_connect($host, $port);
        if (!$conn){
            return false;
        }
        
        // Set the connection resource into the object
        $this->conn = $conn;
        
        // Now try the login
        if (!ftp_login($this->conn, $user, $pass)){
            return false;
        }
        
        // Switch to the requested directory
        if ($dir != '' && !ftp_chdir($this->conn, $dir)){
            return false;
        }

        // Set the working directory
        $this->dir = ftp_pwd($this->conn);
        
        return true;
        
    }

    /**
     * Disconnect from the host
     * @return type
     */
    protected function disconnect() {
        return ftp_close($this->conn);
    }

    /**
     * Change the working directory within the host
     * @param type $params
     * @return boolean
     */
    public function change($params) {
        
        if (!ftp_chdir($this->conn, $params)){
            return false;
        } else {
            $this->dir = ftp_pwd($this->conn);
            return true;
        }
        
    }

    /**
     * 
     * @param type $path
     */
    public function find($path) {
                
        // Check the path is ok
        $filepath = $this->ok($path);
        if (!$filepath){
            return false;
        }
                
        $file = new FTPFile($filepath);
        $file->setStore($this);
        
        return ($file->exists()) ? $file : false;
        
    }

    public function touch($path) {
        
    }

    /**
     * Get an array of all the files and directories in the working directory
     * @return type
     */
    public function listAll() {
        return ftp_nlist($this->conn, $this->dir);
    }
    
    

    /**
     * Check that a given file path is inside the path we specified for our DataStore
     * This prevents us starting in, say, /data/ and then trying to delete or copy a file from outside this directory, by doing ../some/other/dir
     * @param type $file
     * @return type
     */
    public function ok($file){
        
        $file = $this->dir . '/' . $file;
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
        if (ftp_nlist($this->conn, $dirpath) === false && !$this->makeDir($dirpath)){
            return false;
        }
        
        return $file;
        
    }

    protected function makeDir($path) {
        
    }

}