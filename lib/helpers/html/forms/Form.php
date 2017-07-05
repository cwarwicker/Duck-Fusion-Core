<?php

/**
 * Description of Form
 * 
 * @copyright 28-Apr-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace DF\Helpers\html\forms;

use DF\Helpers\Arr;

class Form {

    private $id;
    private $action;
    private $attributes = array();
    private $fields = array();
    private $options = array();
    
    public function __construct($id) {
        $this->id = $id . '_form';
    }
    
    public function setOptions($options){
        $this->options = $options;
        return $this;
    }
    
    public function addOption($opt){
        $this->options[] = $opt;
        return $this;
    }
    
    public function getOptions(){
        return $this->options;
    }
    
    public function reset(){
        $this->id = null;
        $this->action = null;
        $this->attributes = array();
        $this->fields = array();
    }
    
    protected function getNextKey(){
        
        $last = end($this->fields);
        if ($last){
            $key = key($this->fields);
            if (is_int($key)){
                return $key + 1;
            }
        }
        
        return null;
        
    }

    /**
     * This is called after the instantiation of the object, to set the attributes of the form itself, such as method and action
     * @param array $attributes
     */
    public function open( array $attributes ){
                
        // "url" key is for the action
        if (array_key_exists('url', $attributes)){
            df_convert_url($attributes['url']);
            $this->action = \df_html($attributes['url']);
            unset($attributes['url']);
        }
        
        // "files" key is to enable file uploads
        if (array_key_exists('files', $attributes) && $attributes['files'] === true){
            $this->attributes['enctype'] = 'multipart/form-data';
            unset($attributes['files']);
        }
                
        $this->attributes = array_merge($this->attributes, $attributes);
        array_sort($this->attributes, Arr::ARR_SORT_ASC, Arr::ARR_SORT_BY_KEY);
        
        return $this;
        
    }
    
    /**
     * Render the form and return the output
     * @return string
     */
    public function render(){
        
        // Append CSRF token hidden field to the end of the form
        $token = \DF\Helpers\Security::token($this->id);
        $this->add('hidden', 'df_token', $token);
        $this->add('hidden', 'submit_form_' . $this->id, 1);
        
        $output = "";
        
        $output .= "\n<form action='{$this->action}' ".  df_attributes_to_string($this->attributes)." >\n";
        
        if ($this->fields)
        {
            foreach($this->fields as $field)
            {
                $output .= $field->render() . "\n";
            }
        }        
        
        $output .= "</form>\n";
        
        return $output;
        
    }
    
    public function add($type, $name, $value = null, $attributes = null, $options = null, $extras = array()){
        
        $type = strtolower( trim($type) );
        $field = false;
                        
        switch($type)
        {
            case 'hidden':
            case 'text':
            case 'password':
            case 'submit':
            case 'reset':
            case 'button':
            case 'checkbox':
            case 'color':
            case 'date':
            case 'datetime':
            case 'datetime-local':
            case 'email':
            case 'file':
            case 'image':
            case 'month':
            case 'number':
            case 'radio':
            case 'range':
            case 'search':
            case 'tel':
            case 'time':
            case 'url':
            case 'week':
                $field = new \DF\Helpers\html\forms\Input();
                $field->setType($type);
            break;
        
            case 'select':
                $field = new \DF\Helpers\html\forms\Select();
                $field->setOptions($options);
            break;
        
            case 'textarea':
                $field = new \DF\Helpers\html\forms\Textarea();
            break;            
        
        }
        
        // If valid field
        if ($field)
        {
            $field->setName($name);
            $field->setValue($value);
            $field->setAttributes($attributes);
            $field->setExtras( array_merge($this->options, $extras) );
            $this->fields[] = $field;
        }
        
        return $this;
        
    }
    
    /**
     * Get the submitted data from the form, if there is any
     * @return boolean
     */
    public function data(){
        
        $method = $this->getMethod();
        
        switch($method)
        {
            case 'post':
                $data = $_POST;
            break;
            case 'get':
                $data = $_GET;
            break;
            default:
                $data = $_REQUEST;
            break;
        }
        
        // Is this form submitted?
        if (isset($data['submit_form_'.$this->id])){
            return $data;
        }
        
        return false;
        
    }
    
    /**
     * Get an element from the submitted data, by its name
     * @param type $name
     * @return type
     */
    public function get($name){
        
        $data = $this->data();
        return (isset($data[$name])) ? $data[$name] : null;
        
    }
    
    /**
     * Get a specific uploaded file
     * @param type $name
     * @return type
     */
    public function getFile($name){
        return (isset($_FILES[$name])) ? $_FILES[$name] : false;
    }
    
    /**
     * Get the method of the form
     * @return type
     */
    public function getMethod(){
        
        $method = (array_key_exists('method', $this->attributes)) ? strtolower($this->attributes['method']) : false;
        return $method;
        
    }
    
    /**
     * Check if everything is ok with the form - request method matches expected, and token matches
     * @return type
     */
    public function ok(){
        return ($this->isSafe() && $this->isTokenValid());
    }
    
    /**
     * At the moment this only checks the method, and only if it's been defined in the form
     * @return boolean
     */
    public function isSafe(){
                        
        // First check if the method is set in the form and then if the request method matches
        $method = $this->getMethod();
        if ($method)
        {
            if (strcasecmp($method, $_SERVER['REQUEST_METHOD']) !== 0 )
            {
                return false;
            }
        }
        
        return true;
        
    }
    
    /**
     * Check if the token was submitted with the form, and if it matches the token in the user's session
     * @return type
     */
    public function isTokenValid(){
        
        $data = $this->data();
        if (!$data){
            return false;
        }
        
        $token = \DF\Helpers\Security::token($this->id);
        $submitted = (array_key_exists('df_token', $data)) ? $data['df_token'] : false;
        
        return ($submitted === $token);
        
    }
    
   
    
}
