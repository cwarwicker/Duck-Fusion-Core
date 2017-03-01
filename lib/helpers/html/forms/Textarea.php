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
                
        return $output;
        
    }
    
}
