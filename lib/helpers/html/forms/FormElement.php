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
 * FormElement
 * 
 * This abstract class defines the generic methods for form elements, and should be extended into different classes for different element types
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

namespace DF\Helpers\html\forms;

abstract class FormElement {
    
    protected $id;
    
    public $name;
    public $value;
    public $attributes;
    public $extras;
    public $validation;
    public $validation_err_message;
    public $errors;
    
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
    
    public function hasValidation(){
        return (strlen($this->validation) > 0);
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
    
    public function validate(\DF\Helpers\Validation $validator, $data){
        
        echo "Field: $this->name<br>";
        
        $result = $validator->validate( array($this->name => $data), array($this->name => $this->validation) );       
        
        // If it's an array, it's the array of errors
        if (is_array($result)){
            $this->errors = $result;
        }
        
        return $result;
        
    }
    
}
