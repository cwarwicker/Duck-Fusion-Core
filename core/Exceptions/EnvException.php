<?php

namespace DF\Exceptions;

class EnvException extends \Exception {
    
    public static function missingModule($module){
        throw new self( sprintf('Required PHP module (%s) cannot be found', $module) );
    }
    
}