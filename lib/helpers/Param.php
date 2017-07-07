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
 * Param
 * 
 * This Helper class provides various methods for working with user-submitted parameters, such as retrieving the named parameter from the global arrays and sanitising the data
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

namespace DF\Helpers;

abstract class Param {
    
    const TYPE_ALPHA = 'alpha';
    const TYPE_ALPHAEXT = 'alphaext';
    const TYPE_ALPHANUM = 'alphanum';
    const TYPE_ALPHANUMEXT = 'alphanumext';
    const TYPE_BOOL = 'bool';
    const TYPE_EMAIL = 'email';
    const TYPE_INT = 'int';
    const TYPE_FLOAT = 'float';
    const TYPE_RAW = 'raw';
    
    public static function optional($name, $default = null, $type = self::TYPE_RAW){
        
        // Check post first, then get, otherwise return the default
        if (isset($_POST[$name])){
            $param = $_POST[$name];
        } elseif (isset($_GET[$name])){
            $param = $_GET[$name];
        } else {
            $param = $default;
        }
        
        // Sanitise the value
        return self::clean($param, $type);
        
    }
    
    public static function required($name, $type = self::TYPE_RAW){
        
        // Check post first, then get, otherwise return the default
        if (isset($_POST[$name])){
            $param = $_POST[$name];
        } elseif (isset($_GET[$name])){
            $param = $_GET[$name];
        } else {
            // todo - exception
            \df_error('missingparam');
        }
        
    }
    
    /**
     * Clean a variable, stripping out everything else which isn't expected
     * @param type $data
     * @param type $type
     * @param type $trim
     * @return type
     */
    public static function clean($data, $type, $trim = false){
        
        switch($type)
        {
            
            // Clean out everything not a-z
            case self::TYPE_ALPHA:
                $data = preg_replace('/[^a-z]/i', '', $data);
            break;
        
            // Clean out everything not a-z_-
            case self::TYPE_ALPHAEXT:
                $data = preg_replace('/[^a-z_-]/i', '', $data);
            break;
        
            // Clean out everything not a-z0-9
            case self::TYPE_ALPHANUM:
                $data = preg_replace('/[^a-z0-9]/i', '', $data);
            break;
        
            // Clean out everything not a-z0-9_-
            case self::TYPE_ALPHANUMEXT:
                $data = preg_replace('/[^a-z0-9_-]/i', '', $data);
            break;
        
            // Convert to either true or false
            case self::TYPE_BOOL:
                if (is_string($data) && !in_array($data, array('on', 'off', 'yes', 'no', 'true', 'false'))){
                    $data = boolval($data);
                } else {
                    $data = filter_var($data, FILTER_VALIDATE_BOOLEAN);
                }
            break;
        
            // Returns either a valid email address or false
            case self::TYPE_EMAIL:
                $data = filter_var($data, FILTER_VALIDATE_EMAIL);
            break;
                
            // Convert to an int
            case self::TYPE_INT:
                $data = (int)$data;
            break;
        
            // Convert to a float
            case self::TYPE_FLOAT:
                $data = (float)$data;
            break;
        
            // Do nothing
            case self::TYPE_RAW:
                $data = $data;
            break;
        
            
        }
        
        // If we want to trim it, do that now
        if ($trim && is_string($data)){
            $data = trim($data);
        }
        
        return $data;
        
        
        
    }
    
    
    
}
