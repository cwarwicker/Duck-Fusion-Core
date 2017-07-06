<?php


namespace DF\Helpers\datastore;

use DF\Helpers\datastore\stores\LocalStore;
use DF\Helpers\datastore\exception\DataStoreException;

/**
 * Description of DataStore
 *
 * @author Conn
 */
abstract class DataStore {
    
    /**
     * The working directory
     * @var type 
     */
    protected $dir;
    
    /**
     * Temporary variable to override the makeDir method, when checking if a path is ok()
     * @var type 
     */
    protected $tmpNoMake = false;
    
    /**
     * If you want to lock to finding files only inside this specific directory and no sub directories, set this to true with lock()
     * @var bool
     */
    protected $locked = false;
    
    /**
     * Default file permissions to use if we create a new file
     * @var type 
     */
    protected $chmod = 0775;
    
    /**
     * If attempting to copy/move a file into a directory which does not exist, force the creation of the directory
     * @var type 
     */
    protected $forceCreate = false;
    
    
    /**
     * Construct the object and attempt to connect to the data source
     * @param type $params
     * @throws DataStoreException
     */
    public function __construct($params) {
        
        if (!$this->connect($params)){
            DataStoreException::connectionFailed();
        }
               
    }
    
    public function __destruct() {
        $this->disconnect();
    }
    
    public function chmod($chmod){
        $this->chmod = $chmod;
        return $this;
    }
    
    public function lock(){
        $this->locked = true;
        return $this;
    }
    
    public function unlock(){
        $this->locked = false;
        return $this;
    }
    
    public function forceCreate($val = true){
        $this->forceCreate = $val;
        return $this;
    }
    
    abstract protected function connect($params);
    abstract protected function disconnect();
    abstract protected function makeDir($path);
    abstract public function change($params);
    abstract public function find($path);
    abstract public function listAll();
    abstract public function ok($file);
    abstract public function touch($path);
    
}
