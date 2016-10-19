<?php

/**
 * Description of Router
 * 
 * @copyright 28-Apr-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace DF;

abstract class Router
{
    
    protected $defaults = array();
    protected $routes = array();
    
    abstract public function __construct();
    
    /**
     * Get a default controller/action/etc...
     * @param type $name
     * @return type 
     */
    public function getDefault($name){
        return (isset($this->defaults[$name])) ? $this->defaults[$name] : false ;
    }
    
    /**
     * Route a url to an alias
     * @param type $url
     * @return type 
     */
    public function route($url){
                
        // Exact url
        if (isset($this->routes[$url])) return $this->routes[$url];
        
        // Wildcards
        if ($this->routes)
        {
            foreach($this->routes as $route => $new)
            {
                // Check if it ends in a wildcard
                preg_match("/(.*?)\*$/", $route, $matches);
                
                if ($matches)
                {
                    
                    $match = $matches[1];
                    if (preg_match("/^".str_replace("/", "\/", $match)."/", $url))
                    {
                    
                        $urlMatches = explode($match, $url);
                        if ($urlMatches)
                        {
                            $wild = end($urlMatches);
                        }

                        $newUrl = str_replace("*", $wild, $new);

                        return $newUrl;
                    
                    }
                    
                }
                
            }
        }
        
        // Otherwise just return what it currently is
        return $url;
        
    }
    
    
}