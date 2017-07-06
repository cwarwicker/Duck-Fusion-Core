<?php

namespace DF\Helpers;

class Validation extends \GUMP {
    
    protected $rules = array();
    
    public function addRule($el, $validation){
        $this->rules[$el] = $validation;
    }
    
    public function validates($data) {
        return $this->validate($data, $this->rules);
    }
    
}