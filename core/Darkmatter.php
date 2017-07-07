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
 * Darkmatter
 * 
 * Like the building blocks of the universe, this Darkmatter file creates the environment for our DuckFusion application to live
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

if ( (!defined('df_APP') || !defined('df_APP_ROOT')) && !defined('df_CLI')){
    die('Application path not defined. Unable to continue');
    exit;
}

// Set error reporting here, so we can see any errors which occur before we actually load the environment config
error_reporting(E_ALL);

// Define constants
define('df_DS', DIRECTORY_SEPARATOR);
define('df_ROOT', dirname(dirname(dirname(__FILE__))) . df_DS );
define('df_SYS', df_ROOT . 'sys' . df_DS);
define('df_SYS_CORE', df_SYS . 'core' . df_DS);
//define('df_STACK_SIZE', 524); // Incorrect stack size can cause apache to crash/restart with the Quack template engine, due to large strings passed into preg_* functions
//                              // For more info see: http://stackoverflow.com/questions/7620910/regexp-in-preg-match-function-returning-browser-error
//
//ini_set("pcre.recursion_limit", df_STACK_SIZE);

// Load all the vendors
require_once df_SYS . 'lib' . df_DS . 'vendor' . df_DS . 'autoload.php';

// Load all the core system files
require_once df_SYS_CORE . 'autoload.php';

// Load common functions which can be used across all applications
require_once df_SYS . 'common' . df_DS . 'Functions.php';

// Require files
if (!defined('df_CLI')){
    require_once df_APP_ROOT . df_DS .  'config' . df_DS . 'Config.php';
}

ob_start();