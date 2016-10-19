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
    
}