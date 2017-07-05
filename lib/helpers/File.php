<?php

namespace DF\Helpers;

/**
 * 
 * REMOVE THIS AND IMPLEMENT INTO LOCALFILE
 * 
 */

/**
 * Description of File
 *
 * @author Conn
 */
class File {
    
    private $tmpFile;

    protected $name;
    protected $type;
    protected $file;
    protected $error;
    protected $size;


    public function __construct($file = false){
        
        if ($file)
        {
            
            $this->tmpFile = $file;

            // Name
            if (isset($file['name'])){
                $this->name = $file['name'];
            }

            // Mime Type
            if (isset($file['type'])){
                $this->type = $file['type'];
            }

            // Tmp Name
            if (isset($file['tmp_name'])){
                $this->file = $file['tmp_name'];
            }

            // Error code
            if (isset($file['error'])){
                $this->error = $file['error'];
            }

            // Size in bytes
            if (isset($file['size'])){
                $this->size = $file['size'];
            }
        
        }
        
        
        
    }
    
    public function getName(){
        return $this->name;
    }
    
    public function setName($val){
        $this->name = $val;
        return $this;
    }
    
    public function getFile(){
        return $this->file;
    }
    
    public function setFile($val){
        $this->file = $val;
        return $this;
    }
    
    public function getErrorCode(){
        return $this->error;
    }
    
    public function getSize($hr = false){
        
        if (!is_null($this->size)){
            $size = $this->size;
        } elseif ($this->file){
            $size = filesize($this->file);
        }
        
        if ($size){
            return ($hr) ? \df_convert_bytes_to_hr($size) : $size;
        } else {        
            return false;
        }
        
    }

    /**
     * Get the file extension from a file name
     * @param type $filename
     * @return type
     */
    public function getExtension()
    {
        
        if (!$this->name){
            return false;
        }
                
        $filename = strtolower($this->name);
        $exts = explode(".", $filename);
        $n = count($exts) - 1;
        $ext = $exts[$n];
        
        return $ext;
        
    }

    /**
     * Get the mime type of a file
     * @param type $file
     */
    public function getMimeType()
    {
        
        if (!$this->file || !is_readable($this->file)){
            return false;
        }
        
        // If finfo is installed use that
        if (function_exists('finfo_open'))
        {
            $info = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($info, $this->file);
            finfo_close($info);
        }
        
        // Otherwise use the less reliable mime type passed through with the file
        else
        {
            // todo - debug log warning
            $mime = $this->type;
        }
        
        return $mime;
    }
    
    /**
     * Hash the contents of the file with a given algorithm and return the string
     * @param type $method
     * @return null
     * @throws \DF\DFException
     */
    public function checksum($method = 'md5'){
        
        if (!$this->file){
            return null;
        }
        
        $methods = hash_algos();
        if (!in_array($method, $methods)){
            throw new \DF\DFException(df_string("authentication"), df_string("errors:invalidhashmethod"));
            return null;
        }
        
        return hash_file($method, $this->file);
        
        
    }
    
    public function save($path = '', $name = false){
        
        if (!$this->file){
            return false;
        }
        
        if (!$name){
            $name = $this->getName();
        }
        
        // Remove any double slashes or backslashes
        $path = preg_replace("/\\\\/", df_DS, $path);
        $path = preg_replace("/\/{2,}/", df_DS, $path);
                
        // Make sure all the directories exist
        if (!\DF\App::createDataDirectory($path)){
            return false;
        }
        
        $newPath = df_APP_ROOT . df_DS . 'data' . df_DS . $path;
        if (strlen($path) > 0){
            $file = $newPath . df_DS . $name;
        } else {
            $file = $newPath . $name;
        }
                
        // Is it an upload or an existing file we are moving?
        if ($this->tmpFile){
            $result = move_uploaded_file($this->file, $file);
        } else {
            $result = rename($this->file, $file);
        }
        
        return $result;
        
    }

    
    public static function load($file){
        
        $path = df_APP_ROOT . df_DS . 'data' . df_DS . $file;
        if (file_exists($path) && is_readable($path))
        {
            $file = new \DF\Helpers\File();
            $file->setName( basename($path) );
            $file->setFile($path);
            return $file;
        }
        else
        {
            return false;
        }
        
    }
    
    
}
