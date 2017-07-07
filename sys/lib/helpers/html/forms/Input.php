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
 * Input
 * 
 * This class extends the FormElement class and provides a class for generic "Input" form elements
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

namespace DF\Helpers\html\forms;

class Input extends FormElement {
    
    public $type;
        
    public function setType($type){
        $this->type = \df_html($type);
        return $this;
    }
    
    public function getType(){
        return $this->type;
    }
    
    public function render(){
        
        $output = "";
        $output .= "<input id='{$this->getElementID()}' type='{$this->type}' name='{$this->name}' value='{$this->value}' ".  df_attributes_to_string($this->attributes)." />";
        
        if (isset($this->extras['bootstrap']) && $this->extras['bootstrap'] == true){
            $output = $this->applyBootstrap($output);
        }
                
        return $output;
        
    }
    
    protected function applyBootstrap($content){
        
        $output = "";
        
        $hasError = ($this->errors) ? 'has-error' : '';
                
        // Submit/Button
        if ($this->type == 'submit' || $this->type == 'button'){
            $output = $content;
        }
        
        // Checkbox/Radio
        elseif ($this->type == 'checkbox' || $this->type == 'radio'){
            $output .= "<div class='{$this->type} {$hasError}'>";
            $output .= "<label>";
                $output .= $content;
                if (isset($this->extras['label'])){
                    $output .= $this->extras['label'];
                }
            $output .= "</label>";
            if ($hasError){
                $output .= "<small class='help-block'>{$this->validation_err_message}</small>";
            }
            $output .= "</div>";
        }
        
        // Everything else
        else {
            $output .= "<div class='form-group {$hasError}'>";
                if (isset($this->extras['label'])){
                    $output .= "<label for='{$this->getElementID()}'>{$this->extras['label']}</label>";
                }
            $output .= $content;
            if ($hasError){
                $output .= "<small class='help-block'>{$this->validation_err_message}</small>";
            }
            $output .= "</div>";
        }
        
        return $output;
        
    }
    
}
