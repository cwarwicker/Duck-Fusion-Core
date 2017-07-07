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
 * Environments
 * 
 * This file contains your custom environments, based on the server hostname, where you can setup your own error reporting levels, error handlers, etc...
 * 
 * Examples:
 * 
   $Env = array(
    'DESKTOP-1234' => function(){
    
        // All error reporting
        error_reporting(E_ALL);
        ini_set("display_errors", 1);
        ini_set("log_errors", 1);
        ini_set("error_log", df_APP_ROOT . df_DS . 'tmp' . df_DS . 'logs' . df_DS . 'error.log');
        
        // Use Whoops error handler
        $whoops = new \Whoops\Run();
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
        $whoops->register();
        
    },
    'LIVE-SERVER-1234' => function(){
        
        // No error reporting at all
        error_reporting(0);
        ini_set("display_errors", 0);
        ini_set("log_errors", 0);
        
    }
);
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

$Env = array(
    
);