<?php

namespace DF\Helpers\datastore\files;

use DF\Helpers\datastore\exception\FileException as FileException;

/**
 * Description of LocalFile
 *
 * @author Conn
 */
class FTPFile extends \DF\Helpers\datastore\File {
       
    /**
     * Check to see if the file exists
     * @return bool
     */
    public function exists(){
        return (ftp_size($this->store->conn, $this->file) > -1);
    }
    
    /**
     * Check to see if the file is readable by the web server
     * @return bool
     */
    public function readable(){
        return (!empty( ftp_nlist($this->store->conn, $this->file)) );
    }
    
    /**
     * Not really able to do this with FTP, so just returns null
     * @return bool
     */
    public function writable(){
        return null;
    }
    
    /**
     * Copy the file to another location on the FTP server
     * @param type $target
     * @return type
     */
    public function copy($target, $failOnExist = false) {
        
        global $cfg;
                
        // Firstly make sure the target path is ok
        $targetpath = $this->store->ok($target);
        if (!$targetpath){
            return false;
        }
                
        // Then check if the target file already exists
        $find = $this->store->find($target);
        if ($find && $failOnExist){
            return false;
        }

        // Then download the remote file into a temporary local file
        $ds = new \DF\Helpers\datastore\stores\LocalStore($cfg->tmp);
        $tmpfilename = 'tmp-' . string_rand(10);
        $tmpfile = $this->download($ds, $tmpfilename);
        
        // Then upload that temp file back to the remote server in its new location
        $result = $this->store->upload($tmpfile, $target);
                
        // Now delete the temp file
        $tmpfile->delete();
        
        return $result;
        
    }

    /**
     * Delete the file from the FTP server
     * @return type
     */
    public function delete() {
        return ftp_delete($this->store->conn, $this->getFullPath());
    }
                
    /**
     * Move the file to another location on the FTP server
     * @param type $target
     * @param type $newName
     * @param type $failOnExist
     */
    public function move($target = '', $newName = false, $failOnExist = false) {
        
        $newPath = $target . '/' . ( ($newName) ? $newName : $this->getFileName() );
        $result = $this->copy($newPath, $failOnExist);
        if ($result){
            return $this->delete();
        } else {
            return false;
        }
        
    }

    /**
     * Read the contents of the file
     * @return type
     */
    public function read() {
        
        ob_start();
            ftp_get($this->store->conn, "php://output", $this->file, FTP_BINARY);
            $data = ob_get_contents();
        ob_end_clean();
        
        return $data;
    }

    /**
     * Write content to the file
     * @param type $data
     * @param type $flags The same flags used in file_put_contents, e.g. to specify if you want to append instead of overwrite
     * @return type
     */
    public function write($data, $flags = null) {
        // TODO
    }

    /**
     * Try and download a remote file into a LocalStore
     * @param \DF\Helpers\datastore\stores\LocalStore $store
     * @param type $newName
     * @return boolean
     */
    public function download(\DF\Helpers\datastore\stores\LocalStore $store, $newName = false){
                
        $result = false;
        
        $filename = ($newName) ? $newName : $this->getFileName();
        $filepath = $store->ok($filename);
        if ($filepath){
            $result = ftp_get($this->store->conn, $filepath, $this->file, FTP_BINARY);
        }
                
        return ($result) ? $store->find($filename) : $result;
        
    }
    
    
    /**
     * Get the last modified date of the file
     * @return \DateTime
     */
    public function getModified() {
        $timestamp = ftp_mdtm($this->store->conn, $this->file);
        $time = new \DateTime();
        $time->setTimestamp($timestamp);
        return $time;
    }

   

}
