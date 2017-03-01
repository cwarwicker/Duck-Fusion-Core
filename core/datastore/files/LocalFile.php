<?php

namespace DF\datastore\files;
/**
 * Description of LocalFile
 *
 * @author Conn
 */
class LocalFile extends \DF\datastore\File {
   
    /**
     * Check to see if the file exists
     * @return bool
     */
    public function exists(){
        return file_exists($this->file);
    }
    
    /**
     * Check to see if the file is readable by the web server
     * @return bool
     */
    public function readable(){
        return is_readable($this->file);
    }
    
    /**
     * Check to see if the file is writable by the web server
     * @return bool
     */
    public function writable(){
        return is_writeable($this->file);
    }
    
    /**
     * Attempt to create a file at a given target location
     * @param string $target Full path of the file to create
     * @param bool $createDirs TRUE|FALSE - Attempt to create any directories in the path, which do not exist
     */
    public static function touch($target, $createDirs = true){
        
        
        
    }

    public function copy($target) {
        
    }

    public function delete() {
        
    }

    public function move($target) {
        
    }

    public function read() {
        
    }

    public function write($content) {
        
    }

}
