<?php

namespace DF\Exceptions;

class FileExistException extends \Exception {
    
    public static function fileDoesNotExist($file){
        throw new self( sprintf('Cannot load file - %s', $file) );
    }
    
}