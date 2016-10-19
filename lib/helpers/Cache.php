<?php

/**
 * Description of Cache
 * 
 * @copyright 16-Jun-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace DF\Helpers;



class Cache {

    public function __construct() {
        
    }

    public function __destruct() {
        
    }
    
    /**
     * Find an old cached file
     * @param type $module
     * @param type $controller
     * @param type $action
     * @param type $params 
     */
    public static function findCache($module, $controller, $action, $params, $expires = 3600){
        
        if (!is_array($params)) $params = array($params);
        
        $file = '';
        if ($module) $file .= $module . '.';
        $file .= $controller . '.' . $action . '.';
        
        // Strip any slashes from params, in case someone is trying to do something like /myapp/report/1/../../../../etc/passwd or similar
        $params =  implode(".", $params);
        $params = preg_replace('/\/|\\\/', '', $params);
        if (!empty($params)) $file .= $params;
        
        $now = time();
        
        $file = df_APP_ROOT . df_DS . 'tmp' . df_DS . 'cache' . df_DS . $file;
                
        if (file_exists($file)){
            
            $expire = filemtime($file) + $expires;
            if ($now > $expire){
                // Delete cached file and return false
                unlink($file);
                return false;
            }
            else 
                // Return the cached file
                return file_get_contents($file);
        } else {
            return false;
        }
                
    }
    
    /**
     * Cache output to a file
     * @param type $module
     * @param type $controller
     * @param type $action
     * @param type $params
     * @param type $content
     * @return type 
     */
    public static function cache($module, $controller, $action, $params, $content){
        
        if (!is_array($params)) $params = array($params);
        
        $file = '';
        if ($module) $file .= $module . '.';
        $file .= $controller . '.' . $action . '.';
        
        // Strip any slashes from params, in case someone is trying to do something like /myapp/report/1/../../../../etc/passwd or similar
        $params =  implode(".", $params);
        $params = preg_replace('/\/|\\\/', '', $params);
        if (!empty($params)) $file .= $params;
                
        $fh = fopen(df_APP_ROOT . df_DS . 'tmp' . df_DS . 'cache' . df_DS . $file, 'a+');
        if ($fh)
        {
            fwrite($fh, $content);
            fclose($fh);
            return true;
        }
        
        return false;
        
    }

}
