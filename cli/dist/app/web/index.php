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
 * Index
 * 
 * This is the Index page of your application, through which everything is routed
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

define('df_APP_ROOT', dirname(dirname(__FILE__)));
define('df_APP', basename(df_APP_ROOT));

// Try to include the Darkmatter file, which will in turn include everything else we need
if (!@include_once('../../../sys/core/Darkmatter.php')){
    die("Could not load Darkmatter file. Unable to continue.");
}

// Setup stuff like error levels, etc...
df_setup();

// Call the router to work out where we are going
df_call_routing();

// Exit script
df_stop();