<?php
/**
 * Description of Controller
 * 
 * @copyright 28-Apr-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace DF;

use DF\Router as Router;

abstract class Controller {
    
    protected $module = false;
    protected $controller = false;
    protected $action = false;
    protected $params = null;
    
    protected $models = array();
    protected $template = false;
    
    protected $cache = array();
    
    // Look at this authentication thing - don't know if it really does anything yet
    protected $requireAuthentication = false;
    protected $requireAuthenticationRedirect = '';

    public function __construct($module) {
        
        global $cfg, $User;
                
        // Do we need to be logged in to use this Controller?
        if ($this->requireAuthentication){
            
            // If the $User variable is not set (which should be set in the application's Session.php file), redirect
            if (!$User){
                Router::go($cfg->www . '/' . $this->requireAuthenticationRedirect);
            }
            
        }
                               
        $this->module = $module;
        $this->controller = $this->getShortName();
                
        // If there are models to load, load them
        if (!empty($this->models)){
            
            foreach($this->models as $model){
                
                if ($this->module){
                    $file = df_APP_ROOT . df_DS . 'modules' . df_DS . $this->module . df_DS . 'models' . df_DS . $model . '.php';
                } else {
                    // By default classes at site level are in "classes" there is no "models" folder, so this will almost certainly fail
                    // if you want to include classes from the "classes" folder, you should use \DF\App::uses("classname");
                    $file = df_APP_ROOT . df_DS . 'models' . df_DS . $model . '.php';
                }
                
                if (file_exists($file)){
                    require_once $file;
                }
                
            }
            
        }
        
        // Work out where the template is - /views/IndexTemplate or in a module/views/WhateverTemplate
        $Template = array();
        $Template['Name'] = $this->controller;
        
        if ($this->module){
            $Template['Class'] = "\\DF\\App\\" . df_APP . "\\". $module . "\\" . ucfirst($Template['Name']) . 'Template';
            $Template['Path'] = df_APP_ROOT . df_DS . 'modules' . df_DS . $this->module . df_DS . 'views' . df_DS;
        } else {
            $Template['Class'] = "\\DF\\App\\" . df_APP . "\\".ucfirst($Template['Name']) . 'Template';
            $Template['Path'] = df_APP_ROOT . df_DS . 'views' . df_DS;
        }

        $Template['Path'] = $Template['Path'] . ucfirst($Template['Name']).'Template.php';
                        
        try {
            if(file_exists($Template['Path'])){
                require_once($Template['Path']);
            } else {
                throw new \DF\DFException(df_string("template"), df_string("errors:couldnotloadfile"), $Template['Path']);
            }
        } catch(\DF\DFException $e){
            ob_end_clean();
            echo $e->getException();
            df_stop();
        }
                        
        // Load the template class
        $this->template = new $Template['Class']($this->module);
        $this->template->setController($this->controller);
                
    }
    
    /**
     * Get the name of the controller, without "Controller" on the end
     * @return type
     */
    protected function getShortName(){
        
        $className = get_class($this);
        $explode = explode("\\", $className);
        $className = array_pop($explode);
        $pos = strrpos($className, 'Controller');
        $className = substr_replace($className, "", $pos, strlen('Controller'));
        return $className;
        
    }
    
    /**
     * Set the action
     * @param type $action
     * @return $this
     */
    public function setAction($action){
        $action = str_replace('-', '_', $action);
        $this->action = $action;
        if ($this->template){
            $this->template->setAction($action);
        }
        return $this;
    }
    
    /**
     * Set the params to be passed into the action
     * @param type $params
     * @return $this
     */
    public function setParams($params){
        $this->params = $params;
        if ($this->template){
            $this->template->setParams($params);
        }
        return $this;
    }
    
    /**
     * Run the controller
     */
    public function run(){
                               
        global $cfg;

        // If the action is set, but it doesn't exist, produce an error page
        if ($this->action && !$this->hasAction($this->action) && $this->action !== '404'){
            \DF\Router::go($cfg->www . '/404');
        }
        
        // if there is an action, let's try and do that before we load any template
        if ($this->action){
            $this->loadAction($this->action, $this->params);
        }              
        
    }
    
    /**
     * Check if the controller has a specific action method
     * @param type $action
     * @return type
     */
    protected function hasAction($action){
        return method_exists($this, $action);
    }
    
    /**
     * Call the action method on the controller and on its template
     * @param type $action
     * @param type $params
     */
    protected function loadAction($action, $params){
                
        // If the method exists, try to call it - Should do a debug message if it doesn't exist
        if ($this->hasAction($action)){
            call_user_func( array($this, $action), $params);
        }
        
        if ($this->template){
            $this->template->loadAction($action, $params);
        }
        
        // if we want to cache this action, cache it
        if (array_key_exists($action, $this->cache) && array_key_exists('life', $this->cache[$action])){
            $this->template->cache = $this->cache[$action];
            $this->template->cache_output = true;
        }
        
    }
    
    /**
     * Load a helper file
     * TODO - should change to include try/catch probably
     * @param mixed $helper 
     */
//    Don't think i need this, as it's got the helper autoloader in the App class now
//    protected function loadHelper($helper){
//        
//        if (is_array($helper)){
//            foreach($helper as $help){
//                $this->loadHelper($help);
//            }
//        } else {
//            $class = "\\DF\\Helpers\\{$helper}";
//            $reflection = new \ReflectionClass( $class );
//            if (!$reflection->isAbstract()){
//                $this->$helper = new $class();
//                return $this->$helper;
//            } else {
//                // debug log - abstract so cannot load into controller
//            }
//        }
//        
//    }

    /**
     * Get the template object
     * @return type
     */
    public function getTemplate(){
        return $this->template;
    }
    
    
    
        

}