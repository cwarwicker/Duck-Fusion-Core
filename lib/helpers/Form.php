<?php

/**
 * Description of Form
 * 
 * @copyright 28-Apr-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace DF\Helpers;

class Form {

    private $attributes = array();
    private $fields = array();
    
    public function __construct() {
        
    }

    public function __destruct() {
        
    }

    
    
    public static function hidden($name, $value, array $options = null){
        
        $output = "";
        
        $name = \df_html($name);
        $value = \df_html($value);
        $attributes = ($options) ? \df_attributes_to_string($options) : '';
        
        $output .= "<input type='hidden' name='{$name}' value='{$value}' {$attributes} />";
        
        return $output;
        
    }
    
    
    public static function open ($id, array $options)
    {
        
        $output = "";
        $action = "";
        $attributes = "";
        
        // "url" key is for the action
        if (array_key_exists('url', $options)){
            $action = \df_html($options['url']);
            unset($options['url']);
        }
        
        // "files" key is to enable file uploads
        if (array_key_exists('files', $options) && $options['files'] === true){
            $attributes .= 'enctype="multipart/form-data" ';
            unset($options['files']);
        }
        
        // Convert all the rest
        $attributes .= \df_attributes_to_string($options);
        
        $output .= "<form action='{$action}' {$attributes}>";
        
        // CSRF token
        $token = \DF\Helpers\Security::token($id . '-form');
        $output .= self::hidden('token', $token);
        
        return $output;
        
    }
    
    public static function close()
    {
        $output = "";
        $output .= "</form>";
        return $output;
    }
    
}
