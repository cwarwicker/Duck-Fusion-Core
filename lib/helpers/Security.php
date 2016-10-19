<?php

/**
 * Description of Security
 * 
 * @copyright 28-Apr-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace DF\Helpers;

class Security {

    public static function token($name, $create = true){
        
        $key = '_df_tkn_' . $name;
        
        // Check session is started
        if (session_status() !== PHP_SESSION_ACTIVE){
            throw new \DF\DFException( df_string('security'), df_string('errors:sessionnotstarted') );
        }
        
        if (!isset($_SESSION[$key]) && $create){
            $_SESSION[$key] = bin2hex( random_bytes(32) );
        }
        
        return $_SESSION[$key];
        
    }
    
    public static function isTokenValid($name, $value){
        
        $token = self::token($name);
        return ($token === $value);
        
    }

}
