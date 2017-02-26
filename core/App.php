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

    public static function name(){
        return df_APP;
    }
    
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
     * Load a class from your application's "classes" directory for use
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
    
    public static function loadAllHelpers($dir = false){
        
        // Set default helpers directory if not passed through
        if (!$dir){
            $dir = df_SYS . 'lib' . df_DS . 'helpers';
        }
                
        // Require all the .php filers
        $scan = glob($dir . df_DS . '*');
        
        foreach ($scan as $path) {
                                    
            if (preg_match('/\.php$/', $path)) {
                require_once $path;
            }
            elseif (is_dir($path)) {
                self::loadAllHelpers($path);
            }
            
        }
                
    }
    
    
}