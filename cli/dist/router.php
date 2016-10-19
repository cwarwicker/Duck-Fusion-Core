<?php
namespace DF\%ns%;

class Router extends \DF\Router {
    
    public function __construct() {
        
        // Defaults
        $this->defaults['Controller'] = 'index';
        $this->defaults['Action'] = false;
        $this->defaults['Path'] = df_APP_ROOT . df_DS . 'controllers' . df_DS . 'IndexController.php';
        $this->defaults['Class'] = 'indexController';
        
    }

}