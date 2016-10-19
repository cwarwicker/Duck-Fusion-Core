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
    trigger_error('Application path not defined. Unable to continue', E_USER_ERROR);
    exit;
}

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

require df_SYS_CORE . 'Controller.php';
require df_SYS_CORE . 'Model.php';
require df_SYS_CORE . 'Quack.php';
require df_SYS_CORE . 'Template.php';
require df_SYS_CORE . 'Parser.php';
require df_SYS_CORE . 'Router.php';
require df_SYS_CORE . 'Exception.php';
require df_SYS_CORE . 'Database.php';
require df_SYS_CORE . 'App.php';

// Load all the Helpers
// todo - put this in a function
foreach ( glob(df_SYS . 'lib' . df_DS . 'helpers' . df_DS . '*') as $helper )
{
    if (is_file($helper))
    {
        require $helper;
    }
    elseif (is_dir($helper))
    {
        foreach ( glob($helper . df_DS . '*') as $helper )
        {
            if (is_file($helper))
            {
                require $helper;
            }
        }
    }
}

require df_SYS . 'common'.df_DS.'Functions.php';

// Require files
if (!defined('df_CLI')){
    require df_APP_ROOT . df_DS .  'config' . df_DS . 'Config.php';
}

ob_start();