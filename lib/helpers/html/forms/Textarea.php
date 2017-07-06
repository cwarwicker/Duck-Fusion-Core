<?php

namespace DF\Helpers\html\forms;


/**
 * Description of Input
 *
 * @author Conn
 */
class Textarea extends FormElement {
        
    public function render(){
        
        $output = "";
        $output .= "<textarea name='{$this->name}' ".  df_attributes_to_string($this->attributes).">{$this->value}</textarea>";
                
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
