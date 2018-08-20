<?php
/*

    This file is part of the DuckFusion Framework.

    This is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    DuckFusion Framework is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with DuckFusion Framework.  If not, see <http://www.gnu.org/licenses/>.

*/

/**
 *
 * Template
 * 
 * This is the case Template class from which all Templates are extended
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

namespace DF;

use DF\Renderer;

class Template {

    protected $module = false;
    protected $controller = false;
    protected $action;
    protected $params;
    protected $parser = false;
    protected $vars = array();
    protected $breadcrumbs = array();
    
    public $cache = array();
    public $cache_output = false;

    protected $engine = 'DF\Quack';
    protected $setFile = false;
    protected $reserved = array();
    
    private $rendered = false;
    
    
    
    public function __construct($module = false) {
        
        global $cfg, $User;
        
        // Set the parsing engine to use to render the templates
        $this->setEngine(new $this->engine());

        // Set the module if we are in one
        $this->module = $module;
        
        // Set the global variables to be accessible in every template and then set their names to be reserved so they cannot be overwritten
        $this->set("TPL", $this);
        $this->set("cfg", $cfg);
        $this->set("User", $User);
        $this->reserved = array('cfg', 'User', 'TPL');
        
    }
    
    /**
     * Set the parsing engine
     * @param \DF\Parser $engine
     * @return $this
     */
    public function setEngine(Renderer $engine){
        $this->engine = $engine;
        return $this;
    }
    
    /**
     * Get the render engine being used by this template
     * @return type
     */
    public function getEngine(){
        return $this->engine;
    }
    
    /**
     * Set variable to use in template
     * @param type $name
     * @param type $value 
     */
    public function set($name, $value){
        
        if (in_array($name, $this->reserved)){
            // todo - debugging warning
            return $this;
        }
        
        $this->vars[$name] = $value;
        return $this;
    }
    
    /**
     * Take an array of values and set them all as individual variables
     * @param type $array
     * @return $this
     */
    public function multiSet($array){
        
        if ($array){
            foreach($array as $key => $val){
                $this->set($key, $val);
            }
        }
        
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
    
    /**
     * Unset a variable which has previously been set
     * @param type $name
     * @return $this
     */
    public function clear($name){
        unset($this->vars[$name]);
        return $this;
    }
    
    /**
     * Checks if a specific variable has been set
     * @param type $name
     * @return type
     */
    public function has($name){
        return (array_key_exists($name, $this->vars));
    }
    
    /**
     * Gets all the variables which have been set
     * @return type
     */
    public function all(){
        return $this->vars;
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
    
    public function getController(){
        return $this->controller;
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
        } else {
            $view = $this->getAutomatedView();
        }
                                                       
        $this->engine->setVars( $this->vars );
        $this->engine->setRequestString( $this->getRequestString() );
                
        // Cache this action if we are using Cache
        if ($this->cache_output){
            $this->engine->setCaching( (isset($this->cache['type'])) ? $this->cache['type'] : Renderer::CACHE_DYNAMIC );
            $this->engine->setCachingLife($this->cache['life']);
        }

        $this->engine->render($view);
        
        return true;        
                
    }
    
    

}