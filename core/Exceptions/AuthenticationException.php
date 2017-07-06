<?php

namespace DF\Exceptions;

class AuthenticationException extends \Exception {
    
    public static function invalidHashAlgorithm($method){
        throw new self( sprintf('Unsupported hashing algorithm supplied - %s', $method) );
    }
    
}