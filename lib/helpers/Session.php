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
 * Session
 * 
 * This Helper class provides some methods for working with sessions, such as reading and writing to the global $_SESSION array
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

namespace DF\Helpers;

class Session {
    
    public static function init(){
        session_start();
        $_SESSION['_df'] = time();
    }
    
    public static function write($key, $value){
        
        if (!is_string($key)){
            \DF\Exceptions\SessionException::invalidSessionKey();
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

