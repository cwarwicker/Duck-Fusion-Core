<?php

namespace DF\Helpers\datastore\files;
/**
 * Description of LocalFile
 *
 * @author Conn
 */
class LocalFile extends \DF\Helpers\datastore\File {
       
    /**
     * Check to see if the file exists
     * @return bool
     */
    public function exists(){
        return (is_file($this->file) && file_exists($this->file));
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
     * Copy the file to another location
     * @param type $target
     * @return type
     */
    public function copy($target, $failOnExist = false) {
        
        // Make sure we are copying to a directory within the starting directory of the DataStore
        $targetpath = $this->store->ok($target);
        
        // Do we want to fail if the file already exists?
        if ($failOnExist && file_exists($targetpath)){
            return false;
        }
        
        return ($targetpath) ? copy($this->file, $targetpath) : false;
        
    }

    /**
     * Delete the file
     * @return type
     */
    public function delete() {
        return unlink($this->file);
    }
                
    /**
     * Move the file to another location
     * @param type $target
     * @param type $newName (Optional) new file name, otherwise existing name will be used
     * @return type
     */
    public function move($target = '', $newName = false, $failOnExist = false) {
        
        // Make sure the target path is ok
        $targetpath = $this->store->ok( $target . df_DS . (($newName) ? $newName : $this->getFileName()) );
        
        // Do we want to fail if the file already exists?
        if ($failOnExist && file_exists($targetpath)){
            return false;
        }
        
        return ($targetpath) ? rename($this->file, $targetpath ) : false;
        
    }

    /**
     * Read the contents of the file
     * @return type
     */
    public function read() {
        return file_get_contents($this->file);
    }

    /**
     * Write content to the file
     * @param type $data
     * @param type $flags The same flags used in file_put_contents, e.g. to specify if you want to append instead of overwrite
     * @return type
     */
    public function write($data, $flags = null) {
        return file_put_contents($this->file, $data, $flags);
    }

}
