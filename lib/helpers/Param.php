<?php
/**
 * Parameter helper class
 * 
 * Contains methods for hashing passwords, comparing passwords, etc...
 *  * 
 * @copyright 21-Jun-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

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
    
    public static function optional($name, $default, $type = self::TYPE_RAW){
        
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
            \df_error('missingparam');
        }
        
    }
    
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
