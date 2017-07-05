<?php

namespace DF\Helpers\datastore\stores;

use DF\Helpers\datastore\files\FTPFile;

class FTPStore extends \DF\Helpers\datastore\DataStore {
        
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
        $pasv = (isset($params['pasv']) && $params['pasv']);
        
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
        
        // Passive mode on or off
        ftp_pasv(($this->conn), $pasv);
        
        // Switch to the requested directory
        if ($dir != '' && !@ftp_chdir($this->conn, $dir)){
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
        
        if (!@ftp_chdir($this->conn, $params)){
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
                        
        $this->tmpNoMake = true;

        // Check the path is ok
        $filepath = $this->ok($path);
        if (!$filepath){
            $this->tmpNoMake = false;
            return false;
        }

        $this->tmpNoMake = false;

        $file = new FTPFile($filepath);
        $file->setStore($this);
        
        return ($file->exists()) ? $file : false;
        
    }
    
    /**
     * Upload a file to the FTP Store
     * @param \DF\Helpers\datastore\File $file
     * @param type $newName
     * @return type
     */
    public function upload(\DF\Helpers\datastore\files\LocalFile $file, $newName = false){
                
        $filename = (($newName) ? $newName : $file->getFileName());
            
        // Change to that directory?
        $split = explode("/", $filename);
        $realFileName = array_pop($split);
        $filedir = $this->dir . '/' . implode("/", $split);

        if (!@ftp_chdir($this->conn, $filedir)){
            return false;
        };           
            
        $result = ftp_put($this->conn, $realFileName, $file->getFullPath(), FTP_BINARY);
        return ($result) ? $this->find($filename) : false;
        
    }

    /**
     * Find or create a file
     * @param type $path
     * @return type
     */
    public function touch($path, $overwrite = false) {
        
        // If it already exists, just return it
        $file = (!$overwrite) ? $this->find($path) : false;
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
               
        global $cfg;
        
        // Check the path is ok
        $filepath = $this->ok($path);
        if (!$filepath){
            return false;
        }
        
        // Create a tmp blank file in the LocalStore to be uploaded with the specified name, as we can't directly "make" a file on the remote server
        $ds = new LocalStore($cfg->tmp);
        $filename = 'tmp-' . string_rand(10);
        $file = $ds->touch($filename);
        if (!$file) {
            return false;
        }
                
        $result = $this->upload($file, $path);        
        
        // Delete tmp file
        $file->delete();
        
        return $result;
        
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
        
        $file = rtrim($this->dir, '/') . '/' . $file;
        $realpath = realpath($file);
        
        $dirpath = ($realpath) ? dirname($realpath) : dirname($file);
        $chkpath = ($realpath) ? $realpath : $dirpath;
        
        // Replace backslashes with forward slashes to avoid conflicts where the server sets the dir to '/' but the dirname() function uses '\'
        $dirpath = str_replace('\\', '/', $dirpath);
        $chkpath = str_replace('\\', '/', $chkpath);
                        
        // Can't always use realpath here as the file doesn't exist, so will just have to compare the paths and return false if any double dots are found
        if (strpos($chkpath, $this->dir) !== 0 || strpos($chkpath, '..') !== false){
            return false;
        } 
        
        // If we are locked to this specific directory, the directories must match exactly, not be a sub directory
        if ($this->locked && $dirpath !== $this->dir ){
            return false;
        }
        
        // See if we can switch to this directory (only real way of checking if it exists, as an ftp_nlist returns an empty array which could mean it exists and is empty)
        $dirExists = @ftp_chdir($this->conn, $dirpath);
        
        // Switch back
        if ($dirExists){
            ftp_chdir($this->conn, $this->dir);
        }
        
        // If the directory itself does not exist, return false, unless we have forceCreate enabled, then we can try and create it first
        if (!$dirExists && !$this->makeDir($dirpath)){
            return false;
        }
        
        return $file;
        
    }

    /**
     * Make a new directory on the FTP server
     * @param type $path
     * @return boolean
     */
    protected function makeDir($path) {
        
        if ($this->tmpNoMake){
            return false;
        }
                
        // Split the path by directories and try and create all of them if they don't exist
        $workingDir = $this->dir;
        $newPath = str_replace($this->dir, "", $path);
        $split = array_filter( explode("/", $newPath) );
        
        if ($split)
        {
            foreach($split as $dir)
            {
                
                // Can we change to it?
                if (!@ftp_chdir($this->conn, $workingDir . '/' . $dir)){
                    
                    // Try and make the directory
                    if (!@ftp_mkdir($this->conn, $workingDir . '/' . $dir)){
                        return false;
                    }
                    
                    // Set the permissions to the default
                    $this->chmod = 0777;
                    @ftp_chmod($this->conn, $this->chmod, $workingDir . '/' . $dir);
                    
                }
                
                $workingDir .= '/' . $dir;
                
            }
        }

        return true;
        
    }

}