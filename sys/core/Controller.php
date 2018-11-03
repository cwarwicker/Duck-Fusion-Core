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
 * Controller
 *
 * This abstract class contains the case Controller class, from which all Controllers extend
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

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
    protected $requireAuth = false;
    protected $requireAuthRedirect = '';
    protected $__requireRedirect = '';

    /**
     * Construct the Controller object
     * @param [type] $module [description]
     * @param [type] $action This is not actually loaded into the object here, it is for reference if we need it in __require()
     */
    public function __construct($module, $action) {

        global $cfg, $User;

        // Do we need to be logged in to use this Controller?
        if ($this->requireAuth){

            // If the $User variable is not set (which should be set in the application's Session.php file), redirect
            if (!$User){
                Router::go($cfg->www . '/' . $this->requireAuthRedirect);
            }

        }

        // Is there a __require method, to be called first?
        if (method_exists($this, '__require') && !$this->__require($action)){
          Router::go($cfg->www . '/' . $this->__requireRedirect);
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

        if(file_exists($Template['Path'])){
            require_once($Template['Path']);
        } else {
            \DF\Exceptions\FileException::fileDoesNotExist( $Template['Path'] );
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
    public function setAction($action, $method = null){

        $action = str_replace('-', '_', $action);

        if ($method !== 'GET' && method_exists($this, "{$action}_{$method}")){
            $action .= "_{$method}";
        }

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

        // If this action was cached, try and find it first, so we don't run through all the time-consuming Controller scripts, only to display a cached template
        if ($this->action && array_key_exists($this->action, $this->cache) && array_key_exists('type', $this->cache[$this->action])){

            $cache = $this->template->getEngine()->findCache($this->template->getRequestString(), $this->cache[$this->action]['type']);
            if ($cache){
                $this->template->getEngine()->displayCache($cache, $this->cache[$this->action]['type']);
                exit;
            }

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
     * Get the template object
     * @return type
     */
    public function getTemplate(){
        return $this->template;
    }

    /**
     * Method to be overriden, if we want to do more complex checks before allowing access into a module
     * @param  [type] $action [description]
     * @return [type]         [description]
     */
    protected function __require($action){
      return true;
    }



}
