<?php
namespace DF;

class Router extends \Szenis\Router {
    
    private $defaults = array();
    
    public function __construct() {
        
        parent::__construct();
         
        // Defaults
        $this->defaults['Controller'] = 'IndexController';
        $this->defaults['Action'] = false;
        $this->defaults['Path'] = df_APP_ROOT . df_DS . 'controllers' . df_DS . 'IndexController.php';
                
    }
    
    /**
     * Similar to the RouteResolver::resolve method, but with DF automated routes and controllers/methods integrated
     * @param type $request
     * @return \DF\Controller $controller
     */
    public function route($request){
              
        $routes = $this->getRoutesByMethod($request['method']);
                
        $uri = trim(preg_replace('/\?.*/', '', $request['uri']), '/');
                        
        foreach ($routes as $route) {
            
            $matches = array();

            // if the requested route matches one of the defined routes
            if ($route->getUrl() === $uri || preg_match('~^'.$route->getUrl().'$~', $uri, $matches)) {
                    
                $arguments = $this->getArguments($matches);
                
                // If the action is a closure, simply return that
                if (is_object($route->getAction()) && ($route->getAction() instanceof \Closure)) {
                    return call_user_func_array($route->getAction(), $arguments);
                }
               
                // If we have defined a controller and method, load those instead
                elseif ( strpos($route->getAction(), "::") !== false ){
                                        
                    $return = array(
                        'controller' => false,
                        'action' => false
                    );
                    
                    $explode = explode("::", $route->getAction());
                    $controllerName = trim($explode[0]);
                    $methodName = trim($explode[1]);
                    $module = false;
                    
                    // If the controller name includes a slash, then it is in a module
                    if (strpos($controllerName, "/") !== false){
                        $ctrl = explode("/", $controllerName);
                        $module = trim($ctrl[0]);
                        $controllerName = trim($ctrl[1]);
                    }
                    
                    // Now we have to find the controller file
                    if (isset($module) && strlen($module)){
                        $className = $route->getNamespace() . $module . df_DS . $controllerName;
                        $file = df_APP_ROOT . df_DS . 'modules' . df_DS . $module . df_DS . 'controllers' . df_DS . $controllerName . '.php';                                                
                    } else {
                        $className = $route->getNamespace() . $controllerName;
                        $file = df_APP_ROOT . df_DS . 'controllers' . df_DS . $controllerName . '.php';                                                
                    }
                    
                    // Try to include the file
                    if (!include_once($file)){
                        throw new \DF\DFException(df_string('routing'), df_string('errors:couldnotloadfile'), $file);
                        df_stop();
                    }
                    
                    // Now try and instantiate the controller
                    $return['controller'] = $className;
                    $return['action'] = $methodName;
                    $return['module'] = $module;
                    $return['arguments'] = $arguments;
                    
                    return $return;
                    
                }
                                
            }
            
        }
        
        // If no route was defined for this URI, then work out where to go automatically
        
        $arguments = array();
        $controllerName = false;
        $action = false;
        
        // If no uri, then use the defaults
        if ($uri == '' || $uri == 'index.php'){
                        
            // Try to include the file
            if (!include_once($this->defaults['Path'])){
                throw new \DF\DFException(df_string('routing'), df_string('errors:couldnotloadfile'), $file);
                df_stop();
            }
            
            $return['controller'] = $this->getNamespace() . $this->getDefault('Controller');
            $return['action'] = $this->defaults['Action'];
            
            return $return;
            
        } else {
                        
            // Explode the url by "/"
            $urlArray = preg_split('@/@', $uri, null, PREG_SPLIT_NO_EMPTY);
            
            // First we will try with the first element in the URI being the module
            $module = array_shift($urlArray);
            
            // If there is another element, then it's the controller, e.g. mysite.com/module/report
            if (isset($urlArray[0])) {
                $controllerName = array_shift($urlArray);
            }

            // If there is another element, then it's the action, e.g. mysite.com/module/report/view
            if (isset($urlArray[0])) {
                $action = array_shift($urlArray);
            }

            // Anything else must be params, e.g. mysite.com/module/report/view/1
            $arguments = $urlArray;
            
            // The Control we are going to try and use - First we will check: app/module, so with our example this would be module "reports", controller "report", action "view", params "1"
            // So we're looking for myapp/modules/reports/controllers/reportController->view(1)
            $Control['Name'] = $controllerName;
            $Control['Class'] = $this->getNamespace() . $module . '\\' . ucfirst($Control['Name']) . 'Controller';
            $Control['Path'] = df_APP_ROOT . df_DS . 'modules' . df_DS . $module . df_DS . 'controllers' . df_DS . ucfirst($controllerName).'Controller.php';
           
            
            // If this doesn't exist, let's look for a default controller for this module:
            // myapp/modules/reports/controllers/reportsController->report(view, 1) - This example doesn't make much sense, but it could be anything, e.g. mysite.com/game/stats
            if(!file_exists($Control['Path']))
            {
                if ($action){
                    array_unshift($arguments, $action);
                }
                $action = $controllerName;
                $Control['Name'] = $module;
                $Control['Class'] = $this->getNamespace() . $module . '\\' . ucfirst($Control['Name']) . 'Controller';
                $Control['Path'] = df_APP_ROOT . df_DS . 'modules' . df_DS . $module . df_DS . 'controllers' . df_DS . ucfirst($module).'Controller.php';
            }
                        
            // If that doesn't exist either, let's see if we have an Index defined for this module
            if(!file_exists($Control['Path']))
            {
                $Control['Name'] = 'Index';
                $Control['Class'] = $this->getNamespace() . $module . '\\' . ucfirst($Control['Name']) . 'Controller';
                $Control['Path'] = df_APP_ROOT . df_DS . 'modules' . df_DS . $module . df_DS . 'controllers' . df_DS . ucfirst($Control['Name']).'Controller.php';
            }
            
            // If it STILL doesn't exist, let's look for an application Index
            if(!file_exists($Control['Path']))
            {
                if ($action){
                    array_unshift($arguments, $action);
                }
                $action = $module;
                $module = false;
                $Control['Name'] = 'Index';
                $Control['Class'] = $this->getNamespace() . ucfirst($Control['Name']) . 'Controller';
                $Control['Path'] = df_APP_ROOT . df_DS . 'controllers' . df_DS . ucfirst($Control['Name']).'Controller.php';
            }
                        
            // Try and include the file now, or if we can't find it, then the default file
            if (file_exists($Control['Path'])){
                
                if (!include_once($Control['Path'])){
                    throw new \DF\DFException(df_string('routing'), df_string('errors:couldnotloadfile'), $file);
                    df_stop();
                }
                
                
            } else {
                
                if (!include_once($Router->getDefault('Path'))){
                    throw new \DF\DFException(df_string('routing'), df_string('errors:couldnotloadfile'), $file);
                    df_stop();
                }
                
                $Control['Class'] = $this->getNamespace() . $Router->getDefault('Class');
                
            }
            
            
            $return['controller'] = $Control['Class'];
            $return['action'] = $action;
            $return['arguments'] = $arguments;
            $return['module'] = $module;
            
            return $return;
            
            
        }
        
        
    }
    
    /**
     * Get a default value
     * @param type $name
     * @return type
     */
    public function getDefault($name){
        return (isset($this->defaults[$name])) ? $this->defaults[$name] : false ;
    }
    
    /**
     * Get arguments
     * @param  array $matches
     * @return array
     */
    private function getArguments($matches)
    {
        $arguments = array();

        foreach ($matches as $key => $match) {
            if ($key === 0) continue;

            if (strlen($match) > 0) {
                    $arguments[] = $match;
            }
        }

        return $arguments;
    }
    
    /**
     * Redirect to a URL
     * @param type $url
     */
    public static function go($url){
                        
        ob_end_clean();
        header('Location:'.$url);
        df_stop(); 
        
    }
    
    
}
