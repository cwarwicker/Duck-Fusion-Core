<?php
/**
 * Configuration for your application
 * 
 * @copyright 16-Jun-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

unset($cfg);
global $cfg;

$cfg = new \stdClass();

$cfg->www = '';
$cfg->data = df_APP_ROOT . df_DS . 'data';
$cfg->tmp = df_APP_ROOT . df_DS . 'tmp';

$cfg->title = '%title%';
$cfg->author = '';
$cfg->env = 'dev'; # dev = all errors, notices, warnings, etc..., live = Nothing
$cfg->charset = 'utf-8';


// Database object
$cfg->db_driver = '';
$cfg->db_host = 'localhost';
$cfg->db_name = '';
$cfg->db_user = '';
$cfg->db_pass = '';
$cfg->db_prefix = '';
$cfg->db_charset = 'utf8';

// Extensions
$cfg->config_table = 'config';