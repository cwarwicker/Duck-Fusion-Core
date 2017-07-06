<?php
/**
 * Main configuration file for DuckFusion
 * 
 * @copyright 28-Apr-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

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