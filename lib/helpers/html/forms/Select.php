<?php

namespace DF\Helpers\Form;


/**
 * Description of Input
 *
 * @author Conn
 */
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
                
        return $output;
        
    }
    
}
