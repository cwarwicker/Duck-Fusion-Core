<?php

/**
 * Description of Template
 * 
 * @copyright 28-Apr-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace DF;

class Template {

    protected $module = false;
    protected $controller = false;
    protected $action;
    protected $params;
    protected $parser = false;
    protected $vars = array();
    protected $breadcrumbs = array();
    public $cache_output = false;
    private $rendered = false;
    protected $setFile = false;
    
    public function __construct($module = false) {
        global $cfg, $User;
        $this->module = $module;
        $this->set("cfg", $cfg);
        $this->set("User", $User);
    }
    
    /**
     * Set variable to use in template
     * @param type $name
     * @param type $value 
     */
    public function set($name,$value){
        $this->vars[$name] = $value;
        return $this;
    }
    
    /**
     * Get a variable which has been set for the template use
     * @param type $var
     * @return type 
     */
    public function get($var){
        return (isset($this->vars[$var])) ? $this->vars[$var] : null;
    }
    
    public function setController($controller){
        $this->controller = $controller;
        return $this;
    }
     
    public function setAction($action){
        $this->action = str_replace('-', '_', $action);
        return $this;
    }
    
    public function setParams($params){
        $this->params = $params;
        return $this;
    }
    
    public function setParser(\DF\Parser $obj){
        $this->parser = $obj;
    }
    
    public function setFile($file){
        $this->setFile = $file;
    }
    
    /**
     * Add a breadcrumb to display on given page
     * @param type $title
     * @param type $link
     */
    public function addBreadcrumb($title, $link = false){
        $this->breadcrumbs[$title] = $link;
        return $this;
    }
    
    public function getModule(){
        return $this->module;
    }
    
    public function getAction(){
        return $this->action;
    }
    
    public function getParams(){
        if (!is_array($this->params)){
            $this->params = array($this->params);
        }
        return $this->params;
    }
    
    public function getBreadcrumbs(){
        return $this->breadcrumbs;
    }
    
    public function getRequestString(){
        
        $str = "";
        
        if ($this->module){
            $str .= $this->module . "/";
        }
        
        if ($this->controller){
            $str .= $this->controller . "/";
        }
        
        if ($this->action){
            $str .= $this->action . "/";
        }
        
        if ($this->params){
            if (is_array($this->params)){
                $str .= implode("/", $this->params) . "/";
            } else {
                $str .= $this->params . "/";
            }
        }
        
        return $str;
        
    }
    
    /**
     * Get the string to output the breadcrumbs
     * @param type $seperator
     */
    public function getDisplayBreadcrumbs($seperator = " <i class='icon-right-open-1'></i> "){
        
        global $cfg;
                
        // First the home page
        $links = array();
        $links[] = "<a href='{$cfg->www}'>".df_string('home')."</a>";
        
        if ($this->breadcrumbs)
        {
            foreach($this->breadcrumbs as $crumb => $link)
            {                
                if ($link){
                    $links[] = "<a href='{$link}'>{$crumb}</a>";
                } else {
                    $links[] = "<span>".$crumb."</span>";
                }
            }
        }
        
        return implode($seperator, $links);
        
    }
    
    /**
     * Call action
     * @param type $action
     * @param type $params 
     */
    public function loadAction($action, $params){
        if (method_exists($this, $action)){
            call_user_func( array($this, $action), $params );
        }
    }
    
    /**
     * Load a helper file
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
    
    /**
     * Based on the module, action, etc... in the url, work out which view file we think needs to be loaded
     */
    public function getAutomatedView(){
        
        $view = false;
        
        // No Main files. If we are in a module, look there first
        if ($this->module && $this->action && file_exists(df_APP_ROOT . df_DS . 'modules' . df_DS . $this->module . df_DS . 'views' . df_DS . $this->action . '.html')){
            $view = df_APP_ROOT . df_DS . 'modules' . df_DS . $this->module . df_DS . 'views' . df_DS . $this->action . '.html';
        }
        // If we're not in a module, look at the site level for this action
        elseif ($this->action && file_exists(df_APP_ROOT . df_DS . 'views' . df_DS . $this->action . '.html')){
            $view = df_APP_ROOT . df_DS . 'views' . df_DS . $this->action . '.html';
        }             
        // Otherwise just load the site index
        elseif (file_exists(df_APP_ROOT . df_DS . 'views' . df_DS . 'index.html')){
            $view = df_APP_ROOT . df_DS . 'views' . df_DS . 'index.html';
        } 
        
        return $view;
        
    }
   
    
    /**
     * Render the template
     * New method to use layouts instead of header and footer files
     * @param $args If a string, load the template of that name from the /views/ directory. If an array, 1st element should be module name and 2nd element should be view name to load from there
     */
    public function render($args = false){
                
        if ($this->rendered){
            return false;
        }
        
        $this->rendered = true;        
                        
        // Did we set a specific file to use?
        if ($this->setFile && file_exists(df_APP_ROOT . df_DS . $this->setFile)){
            $view = df_APP_ROOT . df_DS . $this->setFile;
            $this->setFile = false;
        }
        
        // Main files. If we are in a module, look there first
        elseif ($this->module && $this->action && file_exists(df_APP_ROOT . df_DS . 'modules' . df_DS . $this->module . df_DS . 'views' . df_DS . $this->action . '.html')){
            $view = df_APP_ROOT . df_DS . 'modules' . df_DS . $this->module . df_DS . 'views' . df_DS . $this->action . '.html';
        }
        // If we're not in a module, look at the site level for this action
        elseif ($this->action && file_exists(df_APP_ROOT . df_DS . 'views' . df_DS . $this->action . '.html')){
            $view = df_APP_ROOT . df_DS . 'views' . df_DS . $this->action . '.html';
        }             
        // Otherwise just load the site index
        elseif (file_exists(df_APP_ROOT . df_DS . 'views' . df_DS . 'index.html')){
            $view = df_APP_ROOT . df_DS . 'views' . df_DS . 'index.html';
        } 
                                       
        $Quack = new \DF\Quack();
        $Quack->setVars( $this->vars );
        $Quack->render($view);
                
        // Cache
        if ($this->cache_output){
            $this->loadHelper( array("Cache") );
            // Cache this action
            \DF\Helpers\Cache::cache($this->module, $this->controller, $this->action, $this->params, ob_get_contents());
        }

        return true;        
                
    }
    
    
        
  
    

}