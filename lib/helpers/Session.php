<?php

/**
 * Description of Session
 * 
 * @copyright 28-Apr-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace DF\Helpers;

/**
 * Things to do:
 * - Session expiry
 * - Cookies
 * 
 */
class Session {
    
    public static function init(){
        session_start();
        $_SESSION['_df'] = time();
    }
    
    public static function write($key, $value){
        
        if (!is_string($key)){
            throw new \InvalidArgumentException( \df_string('errors:sessionkey') );
        }
        
        $_SESSION[$key] = $value;
        
        return $_SESSION[$key];
        
    }
    
    public static function delete($key){
        if (array_key_exists($key, $_SESSION) && $key !== '_df'){
            unset($_SESSION[$key]);
            return true;
        } else {
            return false;
        }
    }

    public static function read($key){
        if (array_key_exists($key, $_SESSION)){
            return $_SESSION[$key];
        } else {
            return false;
        }
    }
    
    public static function destroy(){
        unset($_SESSION);
        session_destroy();
    }
    
}

