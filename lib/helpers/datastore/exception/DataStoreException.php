<?php

namespace DF\Helpers\datastore\exception;

class DataStoreException extends \Exception {
    
    public static function connectionFailed(){
        throw new self( 'Connection to datastore failed' );
    }
    
}
