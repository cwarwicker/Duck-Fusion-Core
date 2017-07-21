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
 * Duckfusion System
 * 
 * This class contains the Duckfusion system information, such as version number and required extensions
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

namespace DF;

class System {
    
    const VERSION = '0.1';
    const REQ_EXTENSIONS = array('fileinfo', 'ftp', 'pdo_mysql');
    
    /**
     * Get the version number
     * @return type
     */
    public static function version(){
        return self::VERSION;
    }
    
    /**
     * Get the array of required extensions
     * @return type
     */
    public static function extensions(){
        return self::REQ_EXTENSIONS;
    }
    
    /**
     * Get an array of required extensions which are missing from the server
     * @return type
     */
    public static function missingExtensions(){
        
        $return = array();
        
        foreach(self::extensions() as $ext){
            if (!extension_loaded($ext)){
                $return[] = $ext;
            }
        }
        
        sort($return);
        return $return;
        
    }
    
}