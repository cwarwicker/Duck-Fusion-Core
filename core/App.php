<?php

/**
 * Application class
 * 
 * @copyright 06-Jul-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace DF;

class App {

    /**
     * Get the name of the current application
     * @return type
     */
    public static function name(){
        return df_APP;
    }
    
    /**
     * Register the loadClass method to auto load Helper classes
     */
    public static function register()
    {
        spl_autoload_register(array('\DF\App', 'loadClass'), true);
    }
    
    /**
     * Automatically load Helper classes
     * @param type $name
     */
    public static function loadClass($name){
                
        $namespace = dirname($name);
        $class = basename($name);
                               
        // Is it a helper?
        if (strpos($namespace, 'DF\Helpers') === 0){
            $path = str_replace('DF\Helpers', '', $namespace);
            $file = df_SYS . 'lib' . df_DS . 'helpers' . $path . df_DS . $class . '.php';
            require_once $file;
        }
        
        // Is it an exception?
        elseif (strpos($namespace, 'DF\Exceptions') === 0){
            $path = str_replace('DF\Exceptions', '', $namespace);
            $file = df_SYS . 'core' . df_DS . 'Exceptions' . df_DS . $class . '.php';
            require_once $file;
        }
        
    }
    
    /**
     * Create a new directory within this application's 'data' directory
     * todo - change this to use localstore class
     * @param type $dir
     * @return boolean
     */
    public static function createDataDirectory($dir){
        
        // Check for main data directory
        $data = df_APP_ROOT . df_DS . 'data';
        if (!is_dir( $data )){
            if (is_writeable( df_APP_ROOT )){
                if (!mkdir($data, 0755, true)){
                    return false;
                }
            } else {
                return false;
            }
        }

        // Now try and make the actual dir we want
        if (!is_dir( $data . df_DS . $dir )){
            if (is_writeable( $data )){
                if (!mkdir($data . df_DS . $dir, 0755, true)){
                    return false;
                }
            } else {
                return false;
            }
        }

        // If we got this far must be ok
        return true;
        
    }
    
    /**
     * Load a class from the application's 'classes' directory. Or if a module is specified, then from that module's 'models' directory
     * @param type $class
     * @param type $module
     * @return boolean
     */
    public static function uses($class, $module = false){
        
        if ($module) $file = df_APP_ROOT . df_DS . 'modules' . df_DS . $module . df_DS . 'models' . df_DS . $class . '.php';
        else $file = df_APP_ROOT . df_DS . 'classes' . df_DS . $class . '.php';
                
        if (file_exists($file)){
            require_once $file;
            return true;
        } else {
            return false;
        }
        
    }
    
    /**
     * Rubbish Collection
     * Delete any files in /tmp or /tmp/cache which are older than the specified max lifetime
     * @param type $maxLife Default: 3600 seconds
     * @return type
     */
    public static function rc($maxLife = 3600){
        
        // Timestamp
        $max = time() - $maxLife;
        
        // Count
        $cnt = 0;
        
        // Go through all the files in the app's 'tmp' directory and the 'tmp/cache' directory and delete any which were created longer ago than the maxLife
        $ds = new Helpers\datastore\stores\LocalStore(df_APP_ROOT . df_DS . 'tmp');
        $ds->lock();
        
        $files = $ds->listAll(true);
        if ($files)
        {
            foreach($files as $file)
            {
                // If the file is older than the max lifetime, delete it
                if ($file->getModified()->format('U') < $max)
                {
                    $cnt += (int)$file->delete();
                }
            }
        }
        
        return $cnt;
        
    }
    
    
}