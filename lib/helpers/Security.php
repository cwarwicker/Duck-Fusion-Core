<?php

/**
 * Description of Security
 * 
 * @copyright 28-Apr-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

// Consider how (if) to implement same origin checking

namespace DF\Helpers;

class Security {

    public static function generateToken($length = 32){
        return bin2hex( random_bytes($length) );
    }
    
    public static function token($name, $create = true){
        
        $key = '_df_tkn_' . $name;
        
        // Check session is started
        if (session_status() !== PHP_SESSION_ACTIVE){
            \DF\Exceptions\SessionException::sessionNotStarted();
        }
        
        if (!isset($_SESSION[$key]) && $create){
            $_SESSION[$key] = self::generateToken(32);
        }
        
        return $_SESSION[$key];
        
    }
    
    public static function isTokenValid($name, $value){
        
        $token = self::token($name);
        return ($token === $value);
        
    }

}
