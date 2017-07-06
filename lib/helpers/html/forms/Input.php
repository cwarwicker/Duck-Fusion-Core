<?php

namespace DF\Helpers\html\forms;


/**
 * Description of Input
 *
 * @author Conn
 */
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
