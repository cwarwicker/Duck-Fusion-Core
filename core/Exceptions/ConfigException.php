<?php

namespace DF\Exceptions;

class ConfigException extends \Exception {
    
    public static function www(){
        throw new self( 'Application URL not found. If you have just created this application, please edit your Config.php file.' );
    }
    
    public static function missingSetting($setting){
        throw new self( sprintf('Missing configuration setting (%s) in application Config.php file', $setting) );
    }
    
}