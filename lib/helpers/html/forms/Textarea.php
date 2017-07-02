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
        
        $output .= "<div class='form-group'>";
            if (isset($this->extras['label'])){
                $output .= "<label for='{$this->getElementID()}'>{$this->extras['label']}</label>";
            }
        $output .= $content;
        $output .= "</div>";

        return $output;
        
    }
    
    
}
