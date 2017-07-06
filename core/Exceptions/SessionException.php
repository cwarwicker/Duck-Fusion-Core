<?php

namespace DF\Exceptions;

class SessionException extends \Exception {
    
    public static function sessionNotStarted(){
        throw new self( 'Session has not yet been started' );
    }
    
    public static function invalidSessionKey(){
        throw new self( 'Invalid session key supplied' );
    }
    
}