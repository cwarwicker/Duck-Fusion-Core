<?php

namespace DF;

abstract class Renderer {

    protected $vars = array();
    protected $cache = false;
    protected $cache_life = 0;
    protected $request_string = false;
    protected $cache_dir = false;
    protected $tmp_dir = false;
    
    const CACHE_LIFETIME = 3600; // 1 hour. By default this will be used if caching is enabled and a lifetime hasn't been set
    const CACHE_DYNAMIC = 1; // Cache the PHP to be executed - So that we don't have to parse the views again we can just include it straight away, but it will still be dynamic and show any changes each time, as it is still just calling PHP
    const CACHE_STATIC = 2; // Cache the actual static output of the script


    
    /**
     * Construct the engine object
     */
    public function __construct() {
        
        // Cache directory in application
        $this->cache_dir = df_APP_ROOT . df_DS . 'tmp' . df_DS . 'cache' . df_DS;
        $this->tmp_dir = df_APP_ROOT . df_DS . 'tmp' . df_DS . 'views' . df_DS;
        
    }
    
    /**
     * Set a variable's value
     * @param type $var
     * @param type $val
     * @return $this
     */
    public function set($var, $val){
        
        $this->vars[$var] = $val;
        return $this;
        
    }
    
    /**
     * Set an array of variables
     * @param type $vars
     */
    public function setVars($vars){
        $this->vars = $vars;
    }
    
    /**
     * Set the cache directory to use
     * @param type $dir
     * @return $this
     */
    public function setCacheDir($dir){
        $this->cache_dir = $dir;
        return $this;
    }
    
    /**
     * Set the request string
     * @param type $str
     * @return $this
     */
    public function setRequestString($str){
        $this->request_string = $str;
        return $this;
    }
    
    /**
     * Set the caching type, to be either dynamic or static
     * @param type $val
     * @return $this
     */
    public function setCaching($val){
        
        if ($val == self::CACHE_DYNAMIC || $val == self::CACHE_STATIC){
            $this->cache = $val;
        }
        
        return $this;
        
    }
    
    /**
     * Set the cache lifetime
     * @param type $val
     * @return $this
     */
    public function setCachingLife($val){
        $this->cache_life = $val;
        return $this;
    }
    
    /**
     * Get when the cache will expire
     * @return type
     */
    public function getCacheExpireTime(){
        
        if ($this->cache_life > 0){
            return time() + $this->cache_life;
        }
        
        return time() + self::CACHE_LIFETIME;
        
    }
    
    /**
     * Serve a given file to the browser
     */
    protected function serve($file){
                
        ob_start();
                
        extract($this->vars);
        
        include_once $file;
        
        ob_end_flush();
                
    }
    
    /**
     * Save contents to a file location
     * @param type $content
     * @param type $location
     */
    protected function saveFile($content, $location){
        
        // The PHP file to use
        $handle = fopen($location, 'w');
        if ($handle)
        {
            fwrite($handle, $content);
            fclose($handle);
        }
        
    }
    
    /**
     * Save the rendered content either to a cache, if caching is enabled, or to a tmp file for including
     * @param type $content
     * @return string
     */
    protected function save($content){
        
        $return = array();
        
        $hash = hash('md5', $this->request_string);
        
        // Is caching enabled?
        if ($this->cache){
        
            // Dynamic - Save the php script
            if ($this->cache === self::CACHE_DYNAMIC){
                
                // The script
                $cacheFile = $hash . '.cached';
                $this->saveFile($content, $this->cache_dir . $cacheFile);
                
                // The info
                $jsonFile = $hash . '.info';
                $obj = new \stdClass();
                $obj->request = $this->request_string;
                $obj->type = 'dynamic';
                $obj->time = time();
                $obj->expires = $this->getCacheExpireTime();
                $this->saveFile(json_encode($obj), $this->cache_dir . $jsonFile);
                
            } 
            // Static - Save the final output
            elseif ($this->cache === self::CACHE_STATIC){
                
                // Save as a tmp file as we would if no caching
                $handle = fopen($this->tmp_dir . $hash, 'w');
                if ($handle)
                {
                    fwrite($handle, $content);
                    fclose($handle);
                }
                 
                // Now let's get that content and include it so that it executes as PHP
                $newContent = $this->getScriptOutput( $this->tmp_dir . $hash );
                
                // Delete that tmp file
                unlink( $this->tmp_dir . $hash );
                
                // Now save that as the cached file
                $cacheFile = $hash . '.cached';
                $this->saveFile($newContent, $this->cache_dir . $cacheFile);
                
                // The info
                $jsonFile = $hash . '.info';
                $obj = new \stdClass();
                $obj->request = $this->request_string;
                $obj->type = 'static';
                $obj->time = time();
                $obj->expires = $this->getCacheExpireTime();
                $this->saveFile(json_encode($obj), $this->cache_dir . $jsonFile);
                
            }

            $return['cached'] = $this->cache_dir . $cacheFile;

        } else {
            
            // Otherwise, just create a tmp file, return it and then delete it
            $handle = fopen($this->tmp_dir . $hash, 'w');
            if ($handle)
            {
                fwrite($handle, $content);
                fclose($handle);
            }
            
            $return['file'] = $this->tmp_dir . $hash;
            
        }
        
        return $return;
        
    }
    
    /**
     * Get the contents of an included script
     * @param type $____script
     * @return type
     */
    protected function getScriptOutput($____script){
        
        ob_start();
        extract($this->vars);
        include_once $____script;
        $____newContent = ob_get_contents();
        ob_end_clean();
        
        return $____newContent;
        
    }
    
    /**
     * Find a cache for a given request string and caching type
     * @param type $requestString
     * @param type $type
     * @return boolean
     */
    public function findCache($requestString, $type){
                        
        $now = time();

        // Get current URL string
        if ($type && $this->cache_dir && $requestString){
            
            $hash = hash('md5', $requestString);
            $cacheFile = $hash . '.cached';
            $jsonFile = $hash . '.info';
            
            // Dynamic caching - This means we have cached the PHP script after it was parsed, and still want to execute it
            if ($type === self::CACHE_DYNAMIC)
            {
             
                // Try and find the cached file
                if (file_exists( $this->cache_dir . $cacheFile ))
                {
                    
                    // Is there an info file for it, defining it's properties?
                    // If not, then something is wrong, so do NOT load the cached file and let it execute as normal and create a new one
                    if (file_exists( $this->cache_dir . $jsonFile ))
                    {
                        
                        $info = json_decode(file_get_contents( $this->cache_dir . $jsonFile ));
                        
                        // Make sure it hasn't expired
                        // If it has, do NOT load the cached file, let it go on and create a new one
                        if (isset($info->expires) && $info->expires > $now)
                        {
                            return $this->cache_dir . $cacheFile;
                        }
                        
                    }
                    
                }
                
            }
            
            // Static caching - This means we have cached the actual HTML output, so we just want to display it and do nothing else
            elseif ( $type === self::CACHE_STATIC )
            {
                
                // Try and find the cached file
                if (file_exists( $this->cache_dir . $cacheFile ))
                {
                    
                    // Is there an info file for it, defining it's properties?
                    // If not, then something is wrong, so do NOT load the cached file and let it execute as normal and create a new one
                    if (file_exists( $this->cache_dir . $jsonFile ))
                    {
                        
                        $info = json_decode(file_get_contents( $this->cache_dir . $jsonFile ));
                        
                        // Make sure it hasn't expired
                        // If it has, do NOT load the cached file, let it go on and create a new one
                        if (isset($info->expires) && $info->expires > $now)
                        {
                            return $this->cache_dir . $cacheFile;
                        }
                        
                    }
                    
                }
                
            }
                        
        } 
        
        return false;
        
    }
    
    /**
     * Display a cached file
     * @param type $file
     * @param type $type
     */
    public function displayCache($file, $type){
        
        if (file_exists($file)){
            
            if ($type == self::CACHE_DYNAMIC){
                $this->serve($file);
            } elseif ($type == self::CACHE_STATIC){
                $contents = file_get_contents($file);
                echo $contents;
            }
            
        }
        
    }
    
    
    /**
     * Render a template
     * @param string $view This can be either a full path to a file, a relative path to a file from the app directory or otherwise just the name of a view in the views directory
     */
    public function render($view){
                        
        $now = time();
                                
        $cache = $this->findCache($this->request_string, $this->cache);
        if ($cache){
            $this->displayCache($cache, $this->cache);
            return true;
        }
        
        
        // First check if this is a full path to a file
        if (file_exists($view)){
            // It is, so we will use that
            $file = file_get_contents($view);
        } 
        
        // Next check if it is a relative path from the app directory (adding .html on as it will be left out otherwise)
        elseif ( file_exists(df_APP_ROOT . df_DS . $view . ( ( substr($view, -5, 5) != '.html' ) ? '.html' : '' ) ) ){
            $file = file_get_contents(df_APP_ROOT . df_DS . $view . ( ( substr($view, -5, 5) != '.html' ) ? '.html' : '' ));
        }
        
        // Next check if it's a path to a file in the app's views directory
        elseif ( file_exists(df_APP_ROOT . df_DS . 'views' . df_DS . $view . ( ( substr($view, -5, 5) != '.html' ) ? '.html' : '' ) ) ){
            $file = file_get_contents(df_APP_ROOT . df_DS . 'views' . df_DS . $view . ( ( substr($view, -5, 5) != '.html' ) ? '.html' : '' ));
        }
        
        // Otherwise we failed
        else {
            return false;
        }
                        
        $content = $this->parse($file);
        $result = $this->save($content);
                
        if ($result)
        {
            
            // Now let's actually serve the file to the browser
            
            // Is it a cached file?
            if (isset($result['cached'])){
                $this->serve($result['cached']);
            }
            
            // No, it's just a tmp file
            elseif (isset($result['file']))
            {
                
                $this->serve($result['file']);
                
                // Delete it as it's just a tmp file we won't use again, since we haven't said to cache any of this
                unlink($result['file']);
                
            }
            
        }
        
       return true;
        
    }
    
    
    /**
     * Parse the template and convert to output to be sent to browser
     */
    abstract public function parse($content);
    
    
    
    
    
}

