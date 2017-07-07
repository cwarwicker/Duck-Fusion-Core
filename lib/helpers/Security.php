<?php
/*

    This file is part of the DuckFusion Framework.

    This is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    DuckFusion Framework is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with DuckFusion Framework.  If not, see <http://www.gnu.org/licenses/>.

*/

/**
 *
 * Security
 * 
 * This Helper class provides some methods for working with security, such as random token generation and comparison
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

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
