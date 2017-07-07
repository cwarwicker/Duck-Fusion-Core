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
 * Configuration
 * 
 * This file contains the configuration settings for your application
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

unset($cfg);
global $cfg;

// --------------------
// Configuration settings
// --------------------

$cfg = new \stdClass();


// This should contain the URL to your application (It should NOT include a trailing slash)
$cfg->www = '';

// This should contain the path to your application's data directory
$cfg->data = df_APP_ROOT . df_DS . 'data';

// This should contain the path to your application's temp directory
$cfg->tmp = df_APP_ROOT . df_DS . 'tmp';

// This should contain the timezone you wish to use in your application
$cfg->timezone = 'UTC';

// This should contain the default locale to use in your appllcaiton when looking for language strings
$cfg->locale = 'en';

// This should contain the title of your application
$cfg->title = '%title%';

// This should contain the name of the author of the application
$cfg->author = '';

// This should contain the default environment to use, if different environment configurations have not been setup
// The value 'dev' will log and display all errors
// The value 'live' will log all errors, but display none to the users
$cfg->env = 'dev';

// This should contain the default character set you wish to set in the header for each web page
$cfg->charset = 'utf-8';

// This should contain the database driver to use, if you wish to create a database-driven application, e.g. 'mysql'
$cfg->db_driver = '';

// This should contain the host to connect to
$cfg->db_host = 'localhost';

// This should contain the name of the database to connect to
$cfg->db_name = '';

// This should contain the username to use to authenticate with the database
$cfg->db_user = '';

// This should contain the password to use to authenticate with the database
$cfg->db_pass = '';

// This should contain any prefix you wish to be added to the beginning of database table names when querying them with the builtin Database methods
$cfg->db_prefix = '';

// This should contain the default character set to use when connecting to the database
$cfg->db_charset = 'utf8';

// ----------
// Extensions - These are optional extensions to the configuration object, which can provide you with extra functionality if you wish to use them
// ----------

// This should contain the name of the table in your database (excluding prefix) which contains the 2-column key/value configuration setting data for your application
// This will then automatically load all of that data into the $cfg->config object, so you can easily access it without having to query it
$cfg->config_table = '';