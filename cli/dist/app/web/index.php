<?php

/**
 * Main index page, through which everything is routed
 * 
 * @copyright 16-Jun-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

define('df_APP_ROOT', dirname(dirname(__FILE__)));
define('df_APP', basename(df_APP_ROOT));

// Get the query string 
$URL = (isset($_GET['Qs'])) ? trim($_GET['Qs']) : '';

if (!include_once('../../../sys/core/Darkmatter.php')){
    trigger_error("Could not load Darkmatter file. Unable to continue.");
    exit;
}

// Setup stuff like error levels, etc...
df_setup();

// Call the router to work out where we are going
try {
    df_call_routing($URL);
} catch (\DF\DFException $e){
    echo $e->getException();
}


// Exit script
df_stop();