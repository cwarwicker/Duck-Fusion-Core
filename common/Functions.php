<?php

/**
 * Common functions that might be used across the whole system
 * 
 * @copyright 16-Jun-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

// Some constants
const DF_PAGINATION_RANGE = 3;

// Should I put these constants in their specific Helper file if they have one? Would make it tidier
const DF_AUTH_LOCAL = 1;
const DF_AUTH_LDAP = 2;


/**
 * Stop the script running and push out any output that's in the buffer
 */
function df_stop(){
    ob_end_flush();
    exit;
}

/**
 * Get a string from the language file
 * @param string $string The string to get
 * @param bool $app Are we looking in the application lang file or the system one, default is false
 * @param string $language Shortcode for language, default is 'en'
 * @return string 
 */
function df_string($string, $app = false, $language='en'){
    
    // If language is set use that, otherwise use english
    
    // if using our application's language file, get that, otherwise get the systemone
    if ($app){
        $file = df_APP_ROOT . df_DS . 'lang' . df_DS . $language . df_DS . 'lang.php';
    } else {
        $file = df_SYS . 'lang' . df_DS . $language . df_DS . 'lang.php';
    }
    
    // if the lang file exists, include it
    if (file_exists($file)){
        include $file;
        // If that element is set in the lang array, return it
        if (isset($lang) && isset($lang[$string])) return $lang[$string];
    }
    
    // Else return the string with square brackets to indicate it's missing
    return '[['.$string.']]';
    
}

function df_callRouting($URL = null){
    
    // Require router for this application
    $namespace = "DF\\" . df_APP . "\\";
    $routerClass = "{$namespace}Router";
    $routerFile = df_APP_ROOT . df_DS . 'config' . df_DS . 'Router.php';
    if (!include_once($routerFile)){
        throw new \DF\DFException(df_string('routing'), df_string('errors:couldnotloadfile'), $routerFile);
        df_stop();
    }
    
    // Router
    $Router = new $routerClass();
    
    // Example we are going to use for comments is: mysite.com/reports/report/view/1
    
    $queryString = array();
    $Controller = false;
    $Control = false;
    $Action = false;
    $ControllerName = false;
    $Module = false;
    
    // If URL is null or empty, use the defaults
    if (is_null($URL) || empty($URL)){
        $ControllerName = $Router->getDefault("Controller");
        $Action = $Router->getDefault("Action");
        if (!$ControllerName){
            throw new \DF\DFException(df_string('routing'), df_string('errors:defaultcontrollerundefined'), $routerFile);
            df_stop();
        }
    }
    else
    {
        
        // URL is defined, so first let's see if we want to route it anywhere else
        $URL = $Router->route($URL);
        
        // Explode the url by "/"
        $urlArray = array();
        $urlArray = preg_split('@/@', $URL, null, PREG_SPLIT_NO_EMPTY);
        
        // First element is the module, e.g. mysite.com/module
        $Module = array_shift($urlArray);

        // If there is another element, then it's the controller, e.g. mysite.com/module/report
        if (isset($urlArray[0])) {
                $ControllerName = array_shift($urlArray);
        }

        // If there is another element, then it's the action, e.g. mysite.com/module/report/view
        if (isset($urlArray[0])) {
                $Action = array_shift($urlArray);
        }

        // Anything else must be params, e.g. mysite.com/module/report/view/1
        $queryString = $urlArray;
        
    }
    
    // The Control we are going to try and use - First we will check: app/module, so with our example this would be module "reports", controller "report", action "view", params "1"
    // So we're looking for myapp/modules/reports/controllers/reportController->view(1)
    $Control['Name'] = $ControllerName;
    $Control['Class'] = $namespace . ucfirst($Control['Name']) . 'Controller';
    $Control['Path'] = df_APP_ROOT . df_DS . 'modules' . df_DS . $Module . df_DS . 'controllers' . df_DS . ucfirst($ControllerName).'Controller.php';
    
    // If this doesn't exist, let's look for a default controller for this module:
    // myapp/modules/reports/controllers/reportsController->report(view, 1) - This example doesn't make much sense, but it could be anything, e.g. mysite.com/game/stats
    if(!file_exists($Control['Path']))
    {
        if ($Action){
            array_unshift($queryString, $Action);
        }
        $Action = $ControllerName;
        $Control['Name'] = $Module;
        $Control['Class'] = $namespace . ucfirst($Control['Name']) . 'Controller';
        $Control['Path'] = df_APP_ROOT . df_DS . 'modules' . df_DS . $Module . df_DS . 'controllers' . df_DS . ucfirst($Module).'Controller.php';
    }

    
    // If that doesn't exist either, let's see if we have an Index defined for this module
    if(!file_exists($Control['Path']))
    {
        $Control['Name'] = 'Index';
        $Control['Class'] = $namespace . ucfirst($Control['Name']) . 'Controller';
        $Control['Path'] = df_APP_ROOT . df_DS . 'modules' . df_DS . $Module . df_DS . 'controllers' . df_DS . ucfirst($Control['Name']).'Controller.php';
    }
    
    // If it STILL doesn't exist, let's look for an application Index
    if(!file_exists($Control['Path']))
    {
        if ($Action){
            array_unshift($queryString, $Action);
        }
        $Action = $Module;
        $Module = false;
        $Control['Name'] = 'Index';
        $Control['Class'] = $namespace . ucfirst($Control['Name']) . 'Controller';
        $Control['Path'] = df_APP_ROOT . df_DS . 'controllers' . df_DS . ucfirst($Control['Name']).'Controller.php';
    }
    
    
    // If we can find it now, include that path, otherwise use the defaults
    try {
        if(file_exists($Control['Path']))
        {
            if (@include($Control['Path'])){
                $Controller = new $Control['Class']($Module, $Control['Name']);
            } else {
                throw new \DF\DFException(df_string('routing'), df_string('errors:couldnotloadfile'), $Control['Path']);
            }
        }
        else
        {
            if (@include($Router->getDefault('Path'))){
                $defaultClass = $namespace . $Router->getDefault('Class');
                $Controller = new $defaultClass($Router->getDefault('Controller'), $Router->getDefault('Controller'));
            } else {
                throw new \DF\DFException(df_string('routing'), df_string('errors:couldnotloadfile'), $Router->getDefault('Path'));
            }
        }
    } catch (\DF\DFException $e){
        ob_end_clean();
        echo $e->getException();
        df_stop();
    }
        
    // Set action & params
    $Params = $queryString;
    if (count($Params) == 1){
        $Params = $Params[0];
    }
    
    // If no action at all, use default of "main"
    if ($Action === false) $Action = 'main';
    
    $Controller->setAction($Action);
    $Controller->setParams($Params);
    $Controller->run();
    $Controller->getTemplate()->render();
        
    
}

/**
 * Setup various system settings
 */
function df_setup(){
    
    global $cfg, $db;
    
    // If an evnrionment is set, use that, otherwise we'll assume live to be safe
    if (!isset($cfg->env)){
    
        $cfg->env = 'live';
        
        switch($cfg->env)
        {

            case 'dev':
                error_reporting(E_ALL);
                ini_set("display_errors", 1);
                ini_set("log_errors", 1);
                ini_set("error_log", df_APP_ROOT . df_DS . 'tmp' . df_DS . 'logs' . df_DS . 'error.log');
            break;

            case 'live':
            default:
                error_reporting(0);
                ini_set("display_errors", 0);
                ini_set("log_errors", 0);
            break;
        }
    
    }
    
    // Set headers
    if (isset($cfg->charset)){
        header('Content-Type: text/html; charset='.$cfg->charset);
    }
            
    // If database info is set, let's create a global db object
    if ( isset($cfg->db_driver) && !empty($cfg->db_driver) 
          && isset($cfg->db_host) && !empty($cfg->db_host)
          && isset($cfg->db_name)
          && isset($cfg->db_user) && !empty($cfg->db_user)
          && isset($cfg->db_pass)){
        
                $db = \DF\DB\PDO::instantiate($cfg->db_driver, $cfg->db_host, $cfg->db_user, $cfg->db_pass);
                if ($db){
                    
                    // Connect - If we fail connection set the $db object to false
                    $dbname = ($cfg->db_name != '') ? $cfg->db_name : false;
                    if (!$db->connect($dbname)){
                        $db = false;
                        // err msg?
                        df_stop();
                    }
                    
                    // prefix
                    if ($db && isset($cfg->db_prefix)) $db->setPrefix($cfg->db_prefix);
                    
                }
        
    }
        
    if (file_exists(df_APP_ROOT . df_DS . 'lib.php')){
        require_once df_APP_ROOT . df_DS . 'lib.php';
    }
    
    // Start the DF session
    \DF\Helpers\Session::init();
        
}

/**
 * Log something to a tmp log file
 * @param type $log 
 */
function df_log($log, $file = null){
    
    // If no file defined, log to today's file
    if (is_null($file)){
        $file = date('Ymd', time()) . '.log';
    }
    
    // Open file with append flag
    $fh = fopen( df_APP_ROOT . df_DS . 'tmp' . df_DS . 'logs' . df_DS . $file, 'a+');
    if ($fh){
        if (is_array($log) || is_object($log)){
            fwrite($fh, print_r($log, true) . "\n");
        } else {
            fwrite($fh, $log . "\n");
        }
    }
    fclose($fh);
    
}

/**
 * Run some text through htmlspecialchars with ENT_QUOTES and return the result
 * @param string $text 
 */
function df_html($text){
    global $cfg;
    return htmlspecialchars($text, ENT_QUOTES, $cfg->charset);
}

/**
 * Run some text through df_html and also nl2br
 * @param type $text
 * @return type
 */
function df_text($text){
    return nl2br( df_html($text) );
}

/**
 * Make sure a string isn't empty - this only returns true if it's a string and it is empty, not if it's an array or 0 or anything else
 * @param type $str
 */
function df_empty($str){
    
    $str = (string)$str;
    $str = trim($str);
    
    return ($str === "") ? true : false;
    
}

/**
 * Redirect immediately to another url
 * @param type $url
 */
function df_redirect($url){
    header('Location:' . $url);
    exit;
}

/**
 * Get which theme we are using
 */
function df_get_theme(){
    
    global $cfg;
    
    \DF\App::uses("Setting");
    $class = "\DF\\".df_APP."\Setting";
    $setting = $class::getSetting("theme");
    
    if (!isset($cfg->theme)) $cfg->theme = '';
    
    return ($setting) ? $setting->value : $cfg->theme;
    
}

/**
 * Print a bar to be used when paginating results of something or other
 * @param type $maxPages
 * @param type $currentPage
 * @param type $link
 */
function df_print_page_bar($maxPages, $currentPage, $link, $formData = false, $range = DF_PAGINATION_RANGE){
 
    $output = "";
    
    // Only 1 page - don't bother
    if ($maxPages == 1 || $currentPage > $maxPages)
    {
        return $output;
    }
            
    $next = $currentPage + 1;
    if ($next > $maxPages) $next = $maxPages;
    
    $previous = $currentPage - 1;
    if ($previous < 1) $previous = 1;
    
    if ($formData)
    {
        $formID = "pagination-form-" . mt_rand(1, 9999);
        $output .= "<form id='{$formID}' action='' method='post'>";
        foreach($formData as $data => $value)
        {
            if (is_array($value))
            {
                foreach($value as $v)
                {
                    $output .= "<input type='hidden' name='{$data}[]' value='{$v}' />";
                }
            }
            else
            {
                $output .= "<input type='hidden' name='{$data}' value='{$value}' />";
            }
        }
        $output .= "<input id='current-page-input' type='hidden' name='page' value='{$currentPage}' />";
    }
    
    $output .= "<ul class='pages'>";
    
        $output .= "<li><a href='{$link}{$previous}' class='click-page-number' page='{$previous}'>".\df_string('previous')."</a></li>";
        
        if ($currentPage > 1){
            $output .= "<li><a href='{$link}1' class='click-page-number' page='1'>1</a></li>";
        }
        
        
        
        // If we have more pages than we can list, just list a few either side of the current page
        
        if ($maxPages > $range)
        {
            $before = $currentPage - $range;
            $after = $currentPage + $range;
            
            if ($before > 2){
                $output .= "<li class='pages-skip'>...</li>";
            }
            
            for ($i = $before; $i < $currentPage; $i++)
            {
                if ($i > 1)
                {
                    $output .= "<li><a href='{$link}{$i}' class='click-page-number' page='{$i}'>{$i}</a></li>";
                }
            }
            
            $output .= "<li class='current-page'><a href='#'>{$currentPage}</a></li>";
            
            for ($i = $currentPage + 1; $i <= $after; $i++)
            {
                if ($i < $maxPages)
                {
                    $output .= "<li><a href='{$link}{$i}' class='click-page-number' page='{$i}'>{$i}</a></li>";
                }
            }
            
            if ($after < ($maxPages - 1)){
                $output .= "<li class='pages-skip'>...</li>";
            }
            
        }
        else
        {
            $output .= "<li class='current-page'><a href='#'>{$currentPage}</a></li>";
        }
        
        if ($maxPages > $currentPage){
            $output .= "<li><a href='{$link}{$maxPages}' class='click-page-number' page='{$maxPages}'>{$maxPages}</a></li>";
        }
        $output .= "<li><a href='{$link}{$next}' class='click-page-number' page='{$next}'>".\df_string('next')."</a></li>";
        
    $output .= "</ul>";
    
    if ($formData)
    {
        $output .= "</form>";
        
        $output .= "<script>
        
        $('a.click-page-number').off('click');
        $('a.click-page-number').on('click', function(){

            var page = $(this).attr('page');
            $('#{$formID} #current-page-input').val(page);
            $('#{$formID}').submit();
            return false;

});
        
</script>";
        
    }
    
    echo $output;
    
}



/**
 * Upload a file
 * @param type $tmpLocation
 * @param type $newLocation
 * @param type $name
 */
function df_upload_file($tmpFile, $newLocation, $name){
    
    $return = array(
        'result' => false,
        'error' => false
    );
    
    if (!is_file($tmpFile)){
        $return['error'] = df_string('errors:filenotfound');
        return $return;
    }
    
    if (!is_writable($newLocation)){
        $return['error'] = df_string('errors:dirnotwritable');
        $return['error'] = str_replace("%dir%", $newLocation, $return['error']);
        return $return;
    }
    
    if (!move_uploaded_file($tmpFile, $newLocation . "/" . $name)){
        $return['error'] = df_string('errors:uploadfail');
        return $return;
    }
    
    $return['result'] = true;
    return $return;
    
}

/**
 * Get the file extension of a given file path/name
 * @param type $filename
 * @return type
 */
function df_get_file_extension($filename){
    
    return strtolower(substr( strrchr($filename, '.'), 1 ));
    
}

/**
 * Dump var
 * @param type $var
 */
function df_dump($var){
    
    var_dump($var);
    echo "<hr>";
    
}

/**
 * Unrecoverable error, or just an error we want to stop execution at
 * @param mixed $e string or DFException object
 */
function df_error($e){
        
    // Get current output content
    $content = ob_get_contents();
    
    // Clear all output
    ob_end_clean();
    
    // Print the error page
    echo "TODO - df_error";
    
    
    // Stop
    \df_stop();
    
    
    
}

/**
 * Convert single dimensional array of attributes to a string, to be used as html tag attributes
 * @param type $attributes
 * @return boolean
 */
function df_attributes_to_string($attributes){
    
    if (is_null($attributes) || $attributes === false){
        return false;
    }
    
    // Should only be 1 dimensional
    if (!array_is_multi($attributes)){
        
        $list = array();
        
        foreach($attributes as $key => $val){
            if ($val !== false && !is_null($val)){
                if (is_string($key)){
                    $list[] = \df_html($key) . '="'.\df_html($val).'"';
                } else {
                    $list[] = \df_html($val);
                }
            }
        }
        
        return implode(" ", $list);
        
    }
    
    return false;
    
}




/**
 * Convert a max_filesize value to an int of bytes
 * I'll be honest with you, I can't remember how this works, and looking at it I have no idea... But it doess
 * @param type $val e.g. 128M
 * @return int e.g. ..
 */
function df_get_bytes_from_upload_max_filesize($val)
{
    
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
    
}

/**
 * Convert a number of bytes into a human readable string
 * @param type $bytes
 * @param type $precision
 * @return type
 */
function df_convert_bytes_to_hr($bytes, $precision = 2)
{	
    $kilobyte = 1024;
    $megabyte = $kilobyte * 1024;
    $gigabyte = $megabyte * 1024;
    $terabyte = $gigabyte * 1024;

    if (($bytes >= 0) && ($bytes < $kilobyte)) {
            return $bytes . ' B';

    } elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)) {
            return round($bytes / $kilobyte, $precision) . ' KB';

    } elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
            return round($bytes / $megabyte, $precision) . ' MB';

    } elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
            return round($bytes / $gigabyte, $precision) . ' GB';

    } elseif ($bytes >= $terabyte) {
            return round($bytes / $terabyte, $precision) . ' TB';
    } else {
            return $bytes . ' B';
    }
}


































// These are the global functions you can use instead of calling static methods on the String class


/**
 * Cut a string to a set number of characters, optionally appending something to the end, e.g. "This string is cu..."
 * @param type $str
 * @param type $length
 * @return type
 */
function string_cut($str, $length, $append = ''){
    return \DF\Helpers\Strings::cut($str, $length, $append);
}

/**
 * Generate a random string of a given (or random) length, using given (or default) characters
 * @param type $length
 * @param type $chars
 * @return type
 */
function string_rand($length = false, $chars = false){
    return \DF\Helpers\Strings::rand($length, $chars);
}

/**
* Check if a string starts with specific characters
* @param type $haystack
* @param type $needle If an array is passed, it just has to match at least one of the elements
* @return type
*/
function string_begins($haystack, $needle)
{
    return \DF\Helpers\Strings::begins($haystack, $needle);
}
 
/**
* Check if a string ends with specific characters
* @param type $haystack
* @param type $needle  If an array is passed, it just has to match at least one of the elements
* @return type
*/
function string_ends($haystack, $needle)
{
    return \DF\Helpers\Strings::ends($haystack, $needle);
}
 
/**
* Check if a string contains another string
* @param type $haystack
* @param type $needle
* @return type
*/
function string_contains($haystack, $needle)
{
    return \DF\Helpers\Strings::contains($haystack, $needle);
}

/**
 * Increment a string, as you would a filename, e.g. "test" => "test_1". "test_67" => "test_68", etc...
 * @param type $str
 * @return type
 */
function string_increment($str)
{
    return \DF\Helpers\Strings::increment($str);
}

function string_cycle($str, $name = '', $delim = ',')
{
    return \DF\Helpers\Strings::cycle($str, $name, $delim);
}







 /**
 * Work out the average of all the elements in the array
 * @param array $array
 * @param \Closure $function If this is passed in, then if any elements in the array are arrays or objects, this Closure will be called to return the value you want from it
 * @return type
 */
function array_average(array $array, \Closure $function = null){
    return \DF\Helpers\Arr::avg($array, $function);
}
    
/**
 * Find elements in a multidimensional array, using dot notation, e.g. names.Conn.age
 * @param array $array
 * @param type $find
 * @return boolean
 */
function array_find(array $array, $find){
    return \DF\Helpers\Arr::find($array, $find);
}

/**
 * Check if elements in a multidimensional array exists, using dot notation, e.g. names.Conn.age
 * @param array $array
 * @param type $find
 * @return boolean
 */
function array_has(array $array, $find){
    return \DF\Helpers\Arr::has($array, $find);
}


/**
 * Get elements from an array, where they meet the requirements laid out in the Closure function
 * @param array $array
 * @param Closure $function
 * @return type
 */
function array_where(array $array, Closure $function){
    return \DF\Helpers\Arr::where($array, $function);
}


/**
 * Get the first element in the array to pass the callback test
 * If none pass it, you can use a default parameter to return that as the default value
 * @param type $array
 * @param type $function
 * @param type $default
 */
function array_first(array $array, Closure $function, $default = false){
    return \DF\Helpers\Arr::first($array, $function, $default);
}

/**
 * Get the last element in the array to pass the callback test
 * If none pass it, you can use a default parameter to return that as the default value
 * @param type $array
 * @param type $function
 * @param type $default
 */
function array_last(array $array, Closure $function, $default = false){
    return \DF\Helpers\Arr::last($array, $function, $default);
}


/**
 * Add a key => val relationship onto an existing array. If such an relationship already exists, the flag option will define what we should do
 * @param type $array
 * @param type $key
 * @param type $val 
 */
function array_add(&$array, $key, $val = null, $flag = ARR_EXISTS_SKIP){
    return \DF\Helpers\Arr::add($array, $key, $val, $flag);
}

/**
 * Return two seperate arrays, one of keys, one of values
 * @param array $array 
 * @param bool $recursive If this is true, then if the value is itself an array, it will run array_split over that as well, and any sub-arrays beyond that
 */
function array_split(&$array, $recursive = false){
    return \DF\Helpers\Arr::split($array, $recursive);
}

/**
 * Return an array, excluding any elements with the keys/values as defined
 * @param type $array
 * @param type $exclude 
 */
function array_grep($array, $exclude = array(), $flag = ARR_USE_VALS){
    return \DF\Helpers\Arr::grep($array, $exclude, $flag);
}



/**
 * Flatten a multidimensional array into a single array
 * http://stackoverflow.com/questions/6785355/convert-multidimensional-array-into-single-array
 * @param type $array
 * @return boolean
 */
function array_flatten($array) { 
    return \DF\Helpers\Arr::flatten($array);
} 

/**
 * Check if an array is multidimensional
 * @param type $array
 * @return boolean
 */
function array_is_multi($array){
    return \DF\Helpers\Arr::isMulti($array);
}

/**
 * Sort an array in a given direction
 * @param type $array
 * @param type $order
 * @param type $recursive
 * @return type
 */
function array_sort(&$array, $order, $sortBy = ARR_SORT_BY_VALUE, $recursive = false){
    return \DF\Helpers\Arr::sort($array, $order, $sortBy, $recursive);
}