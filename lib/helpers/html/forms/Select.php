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
 * Select
 * 
 * This class extends the FormElement class and provides a class for "Select" form elements
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

namespace DF\Helpers\html\forms;

class Select extends FormElement {
        
    protected $options;
    
    public function setOptions($options){
        $this->options = $options;
        return $this;
    }
    
    public function addOption($val, $txt){
        $this->options[$val] = $txt;
    }
    
    public function getOptions(){
        return $this->options;
    }
    
    public function render(){

        if (is_null($this->attributes) || !$this->attributes){
            $this->attributes = array();
        }
        
        $output = "";
        
        $name = ( ($key = array_search('multiple', $this->attributes)) !== false && $this->attributes[$key]) ? "{$this->name}[]" : $this->name;
                
        $output .= "<select name='{$name}' ".df_attributes_to_string($this->attributes).">";
        
        if ($this->options)
        {
            foreach($this->options as $val => $txt)
            {
                if (is_array($this->value)){
                    $sel = (in_array($val, $this->value)) ? 'selected' : '';
                } else {
                    $sel = (!\df_empty($this->value) && $this->value == $val) ? 'selected' : '';
                }
                $output .= "<option value='".df_html($val)."' {$sel}>".df_html($txt)."</option>";
            }
        }
        
        $output .= "</select>";
        
        if (isset($this->extras['bootstrap']) && $this->extras['bootstrap'] == true){
            $output = $this->applyBootstrap($output);
        }
                
        return $output;
        
    }
    
    
    
    protected function applyBootstrap($content){
        
        $output = "";
        
        $hasError = ($this->errors) ? 'has-error' : '';
        
        $output .= "<div class='form-group {$hasError}'>";
            if (isset($this->extras['label'])){
                $output .= "<label for='{$this->getElementID()}'>{$this->extras['label']}</label>";
            }
        $output .= $content;
        if ($hasError){
            $output .= "<small class='help-block'>{$this->validation_err_message}</small>";
        }
        $output .= "</div>";

        return $output;
        
    }
    
    
}
