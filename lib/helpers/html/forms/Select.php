<?php

namespace DF\Helpers\html\forms;


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
        
        $output .= "<div class='form-group'>";
            if (isset($this->extras['label'])){
                $output .= "<label for='{$this->getElementID()}'>{$this->extras['label']}</label>";
            }
        $output .= $content;
        $output .= "</div>";

        return $output;
        
    }
    
    
}
