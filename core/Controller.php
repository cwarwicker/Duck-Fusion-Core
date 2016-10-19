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

    public function __construct($module, $controller, $params = false) {
        
        global $cfg, $User;
                
        // Load session file if we can find it
        if (file_exists(df_APP_ROOT . df_DS . 'config' . df_DS . 'Session.php')){
            require_once df_APP_ROOT . df_DS . 'config' . df_DS . 'Session.php';
        }
        
        // Do we need to be logged in to use this Controller?
        if ($this->requireAuthentication){
            
            // Having loaded the session file (or tried to) if we don't have a User variable now, redirect to home page
            if (!$User){
                ob_end_clean();
                header('Location:'.$cfg->www.'?noauth');
                df_stop(); 
            }
            
        }
               
        
        $this->module = $module;
        $this->controller = $controller;
        
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
        $Template['Class'] = "\\DF\\" . df_APP . "\\".ucfirst($Template['Name']) . 'Template';
        
        if ($this->module){
            $Template['Path'] = df_APP_ROOT . df_DS . 'modules' . df_DS . $this->module . df_DS . 'views' . df_DS;
        } else {
            $Template['Path'] = df_APP_ROOT . df_DS . 'views' . df_DS;
        }

        $Template['Path'] = $Template['Path'] . ucfirst($Template['Name']).'Template.php';
                
        try {
            if(file_exists($Template['Path']))
            {
                require_once($Template['Path']);
            }
            else
            {
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
    
    public function setAction($action){
        $action = str_replace('-', '_', $action);
        $this->action = $action;
        if ($this->template) $this->template->setAction($action);
        return $this;
    }
    
    public function setParams($params){
        $this->params = $params;
        if ($this->template) $this->template->setParams($params);
        return $this;
    }
    
    /**
     * Run the controller
     */
    public function run(){
                                             
        // If POST is not empty, let's see if this action has a post method. It's up to you in that specific method to decide whether or not you then want to clear the cache for this action & params
        // You probably will want to, but i'm not going to force you
        if (!empty($_POST) && $this->action){
            $this->loadAction($this->action.'_post', $this->params);
        }
                
        // If this action was cached, tried to find it
        if ($this->action && array_key_exists($this->action, $this->cache)){
            $this->loadHelper( array("Cache") );
            $cache = $this->Cache->findCache($this->module, $this->controller, $this->action, $this->params, $this->cache[$this->action]);
            // If the cache exists and it's not expired, display that instead
            if ($cache){
                echo $cache;
                exit;
            }
        }
        
        
        
        // if there is an action, let's try and do that before we load any template
        if ($this->action){
            $this->loadAction($this->action, $this->params);
        }
        
    }
    
    protected function loadAction($action, $params){
        
        if (method_exists($this, $action)){
            call_user_func( array($this, $action), $params);
        }
        
        if ($this->template) $this->template->loadAction($action, $params);
        
        // if we want to cache this action, cache it
        if (array_key_exists($action, $this->cache)){
            $this->template->cache_output = true;
        }
        
    }
    
    /**
     * Load a helper file
     * TODO - should change to include try/catch probably
     * @param mixed $helper 
     */
    protected function loadHelper($helper){
        
        if (is_array($helper)){
            foreach($helper as $help){
                $this->loadHelper($help);
            }
        } else {
            $class = "\\DF\\Helpers\\{$helper}";
            $reflection = new \ReflectionClass( $class );
            if (!$reflection->isAbstract()){
                $this->$helper = new $class();
                return $this->$helper;
            } else {
                // debug log - abstract so cannot load into controller
            }
        }
        
    }

    public function getTemplate(){
        return $this->template;
    }
        

}