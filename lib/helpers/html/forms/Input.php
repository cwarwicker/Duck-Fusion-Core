<?php

namespace DF\Helpers\Form;


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
        $output .= "<input type='{$this->type}' name='{$this->name}' value='{$this->value}' ".  df_attributes_to_string($this->attributes)." />";
                
        return $output;
        
    }
    
}
