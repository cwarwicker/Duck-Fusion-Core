<?php

namespace DF\Helpers\html\forms;

/**
 * Description of FormElement
 *
 * @author Conn
 */
abstract class FormElement {
    
    protected $id;
    
    public $name;
    public $value;
    public $attributes;
    public $extras;
    
    public function __construct($id = null){
        
        // If no id specified, generate a unique one
        if (strlen($id) < 1){
            $id = 'df_frm_el_' . \string_rand(10);
        }
        
        $this->id = $id;
        
    }
    
    public function getID(){
        return $this->id;
    }    
    
    public function getElementID(){
        return (isset($this->attributes['id'])) ? $this->attributes['id'] : $this->id;
    }
    
    public function setName($name){
        $this->name = \df_html($name);
        return $this;
    }
    
    public function setValue($value){
        if (is_array($value)){
            $this->value = $value;
        } else {
            $this->value = \df_html($value);
        }
        return $this;
    }
    
    public function setAttributes($attributes){
        $this->attributes = $attributes;
        return $this->attributes;
    }
    
    public function addAttribute($key, $val){
        $this->attributes[$key] = $val;
    }
    
    public function getName(){
        return $this->name;
    }
    
    public function getValue(){
        return $this->value;
    }
    
    public function getAttributes(){
        return $this->attributes;
    }
    
    public function getAttribute($key){
        return (array_key_exists($key, $this->attributes)) ? $this->attributes[$key] : null;
    }
    
    public function setExtras($extras){
        $this->extras = $extras;
        return $this;
    }
    
    public function getExtras(){
        return $this->extras;
    }
    
}
