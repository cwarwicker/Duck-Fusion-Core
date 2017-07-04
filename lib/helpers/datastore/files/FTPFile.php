<?php

namespace DF\Helpers\datastore\files;
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
     * Check to see if the file is writable by the web server
     * @return bool
     */
    public function writable(){
        
    }
    
    /**
     * Copy the file to another location on the FTP server
     * @param type $target
     * @return type
     */
    public function copy($target, $failOnExist = false) {
        
       
        
    }

    /**
     * Delete the file from the FTP server
     * @return type
     */
    public function delete() {
        
    }
                
    /**
     * Move the file to another location on the FTP server
     * @param type $target
     * @param type $newName (Optional) new file name, otherwise existing name will be used
     * @return type
     */
    public function move($target = '', $newName = false, $failOnExist = false) {
                
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
        
    }

    /**
     * Try and download a remote file into a LocalStore
     * @param \DF\Helpers\datastore\stores\LocalStore $store
     * @param type $newName
     * @return boolean
     */
    public function download(\DF\Helpers\datastore\stores\LocalStore $store, $newName = false){
                
        $filename = ($newName) ? $newName : $this->getFileName();
        $filepath = $store->ok($filename);
        if ($filepath){
            return ftp_get($this->store->conn, $filepath, $this->file, FTP_BINARY);
        }
        
        return false;
        
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
