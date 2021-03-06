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
 * Common Functions
 *
 * These are all the global functions which may be used across the system/applications
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

// Some constants
const DF_PAGINATION_RANGE = 3;


/**
 * Stop the script running and push out any output that's in the buffer
 */
function df_stop($msg = null){
    if (!is_null($msg)){
      echo $msg;
    }
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
function df_string($string, $app = false, $language = null){

    global $cfg;

    // If language is set use that, otherwise use the default in the configuration file, otherwise just use english
    if (is_null($language)){
        $language = (isset($cfg->locale)) ? $cfg->locale : 'en';
    }

    // if using our application's language file, get that, otherwise get the systemone
    if ($app){
        $file = df_APP_ROOT . df_DS . 'lang' . df_DS . $language . df_DS . 'lang.php';
    } else {
        $file = df_SYS . 'lang' . df_DS . $language . df_DS . 'lang.php';
    }

    // if the lang file exists, include it
    if (file_exists($file)){

        include_once $file;

        // If that element is set in the lang array, return it
        if (isset($lang[$string])){
            return $lang[$string];
        }

    }

    // Else return the string with square brackets to indicate it's missing
    return '[['.$string.']]';

}

/**
 * Route the application to where we want to go, based on the query string
 * @throws \DF\DFException
 */
function df_call_routing(){

    // Default variables
    $controller = false;
    $action = false;
    $arguments = false;

    // Create router object
    $Router = new \DF\Router();
    $Router->setNamespace('DF\\App\\' . df_APP . '\\');

    // Load any application-defined routes
    $routerFile = df_APP_ROOT . df_DS . 'config' . df_DS . 'Routes.php';
    if (!file_exists($routerFile)){
        \DF\Exceptions\FileException::fileDoesNotExist($routerFile);
    }

    include_once $routerFile;

    // Resolve the route
    $resolve = $Router->route( array(
        'uri' => $_SERVER['REQUEST_URI'],
        'method' => $_SERVER['REQUEST_METHOD']
    ) );


    // If we returned an array, then it should contain the controller and method
    if (is_array($resolve)){

        $controller = (strlen($resolve['controller'])) ? $resolve['controller'] : false;
        $action = (strlen($resolve['action'])) ? $resolve['action'] : false;
        $arguments = (isset($resolve['arguments'])) ? $resolve['arguments'] : false;
        $module = (isset($resolve['module'])) ? $resolve['module'] : false;

        $Controller = new $controller($module, $action);

    } else {

        // If we set a route to return something, instead of redirect, just echo it out
        echo $resolve;
        \df_stop();

    }

    // If no action set, use the default "main" method
    if ($action === false){
        $action = 'main';
    }

    $Controller->setAction($action, $_SERVER['REQUEST_METHOD']);
    $Controller->setParams($arguments);
    $Controller->run();
    $Controller->getTemplate()->render();

}

/**
 * Setup various system settings
 */
function df_setup(){

    global $cfg, $db;

    // Register Error Handler first, so that can be used if there are any errors in the setup
    if ($cfg->env == 'dev' && class_exists('\Whoops\Run')){
        $whoops = new \Whoops\Run();
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
        $whoops->register();
    }

    // Make sure that a URL has been specified in the config
    if (strlen($cfg->www) == 0){
        \DF\Exceptions\ConfigException::www();
    }

    // Start the DF session
    \DF\Helpers\Session::init();

    // If an environment is set, use that, otherwise we'll assume live to be safe
    if (!isset($cfg->env)){
        $cfg->env = 'live';
    }


    // If we are using one of the 2 default environments, load them
    switch($cfg->env)
    {

        case 'dev':

            error_reporting(E_ALL);
            ini_set("display_errors", 1);
            ini_set('xdebug.var_display_max_depth', 8);
            ini_set('xdebug.var_display_max_children', 256);
            ini_set('xdebug.var_display_max_data', 1024);

            // Calculate how long page load takes
            \PHP_Timer::start();

        break;

        case 'live':
            error_reporting(E_ALL);
            ini_set("display_errors", 0);
            ini_set("log_errors", 1);
            ini_set("error_log", df_APP_ROOT . df_DS . 'tmp' . df_DS . 'logs' . df_DS . 'error.log');
        break;

        case 'custom':

            // Check for a defined environment, based on server hostname
            if (file_exists(df_APP_ROOT . df_DS . 'config' . df_DS . 'Env.php')){

                include_once df_APP_ROOT . df_DS . 'config' . df_DS . 'Env.php';

                $hostname = gethostname();

                // If the $Env variable is set, check for this hostname
                if (isset($Env) && array_key_exists($hostname, $Env) && is_callable($Env[$hostname])){
                    call_user_func($Env[$hostname]);
                }

            }

        break;


    }


    // Set headers
    if (isset($cfg->charset)){
        header('Content-Type: text/html; charset='.$cfg->charset);
    }

    // Set timezone
    if (isset($cfg->timezone)){
        date_default_timezone_set($cfg->timezone);
    }

    // If they are using composer and have a vendor/autoload.php file, automatically include that
    if (file_exists(df_APP_ROOT . df_DS . 'vendor/autoload.php')){
        require_once df_APP_ROOT . df_DS . 'vendor/autoload.php';
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
                    if ($db && isset($cfg->db_prefix)){
                        $db->setPrefix($cfg->db_prefix);
                    }

                }

    }

    // If we have a database connection, check out Config.php file for any extras we want to load
    if ( (isset($db) && $db) && isset($cfg->config_table) ){

        // Temporarily set the fetch mode for the this query, then reset it afterwards
        $db->get()->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_KEY_PAIR);
        $results = $db->selectAll($cfg->config_table, array(), null, 'setting,value');
        if ($results){
            $cfg->config = (object)$results->all();
        }
        $db->get()->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);

    }



    // If they have defined a lib.php file, automatically include that
    if (file_exists(df_APP_ROOT . df_DS . 'lib.php')){
        require_once df_APP_ROOT . df_DS . 'lib.php';
    }

    // Load the session if its set
    if (file_exists(df_APP_ROOT . df_DS . 'config' . df_DS . 'Session.php')){
        global $User, $Staff;
        require_once df_APP_ROOT . df_DS . 'config' . df_DS . 'Session.php';
    }

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

function df_convert_url(&$url){

    $Validate = new \GUMP();
    $Validate->validation_rules( array('url' => 'required|valid_url') );
    if (!$Validate->run( array('url' => $url) )){
        global $cfg;
        $url = $cfg->www . '/' . $url;
    }

}

function df_get_class_namespace($class) {
    return join(array_slice(explode("\\", $class), 0, -1), "\\");
}

function df_get_class_name($class){
    $explode = explode("\\", $class);
    return array_pop($explode);
}

function df_create_sql_placeholders($params){
    return implode(',', array_fill(0, count($params), '?'));
}



























// These are the global functions you can use instead of calling static methods on the Helper classes


/**
 * Cut a string to a set number of characters, optionally appending something to the end, e.g. "This string is cu..."
 * @param type $str
 * @param type $length
 * @return type
 */
function string_cut($str, $length, $append = ''){
    return \DF\Helpers\Str::cut($str, $length, $append);
}

/**
 * Generate a random string of a given (or random) length, using given (or default) characters
 * @param type $length
 * @param type $chars
 * @return type
 */
function string_rand($length = false, $chars = false){
    return \DF\Helpers\Str::rand($length, $chars);
}

/**
* Check if a string starts with specific characters
* @param type $haystack
* @param type $needle If an array is passed, it just has to match at least one of the elements
* @return type
*/
function string_begins($haystack, $needle)
{
    return \DF\Helpers\Str::begins($haystack, $needle);
}

/**
* Check if a string ends with specific characters
* @param type $haystack
* @param type $needle  If an array is passed, it just has to match at least one of the elements
* @return type
*/
function string_ends($haystack, $needle)
{
    return \DF\Helpers\Str::ends($haystack, $needle);
}

/**
* Check if a string contains another string
* @param type $haystack
* @param type $needle
* @return type
*/
function string_contains($haystack, $needle)
{
    return \DF\Helpers\Str::contains($haystack, $needle);
}

/**
 * Increment a string, as you would a filename, e.g. "test" => "test_1". "test_67" => "test_68", etc...
 * @param type $str
 * @return type
 */
function string_increment($str)
{
    return \DF\Helpers\Str::increment($str);
}

/**
 * Cycle through a list of strings, getting the next one each time this is called
 * Example:
 *
 *  $colours = 'white,red,blue';
 *  foreach($tableRows as $row)
 *  {
 *      $rowColour = Str::cycle($colours, 'colour', ',');
 *  }
 *
 *  This will alternate between white, red and blue and continue to loop through them each time it is called
 * @param type $str The string to cycle through, using the delim to split it
 * @param type $delim Default separator is a comma
 * @param type $id An id is only needed if you are for some reason calling cycle on the same string on the same line of the script, but want multiple versions of the results
 * @return type
 */
function string_cycle($str, $delim = ',', $id = '')
{
    return \DF\Helpers\Str::cycle($str, $delim, $id);
}







/**
 * Total up the values of a given key in the array and work out an average
 * @param array $array
 * @param type $key
 * @return type
 */
function array_average(array $array, $key){
    return \DF\Helpers\Arr::avg($array, $key);
}

/**
 * Total up the values of a given key in the array
 * @param array $array
 * @param type $key
 * @return type
 */
function array_summation($array, $key){
    return \DF\Helpers\Arr::summation($array, $key);
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
 * Delete an element from a multi-dimensional array, using dot notation
 * @param array $array
 * @param type $key
 * @return boolean
 */
function array_delete(array &$array, $key){
    return \DF\Helpers\Arr::delete($array, $key);
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
function array_add(&$array, $key, $val = null, $flag = \DF\Helpers\Arr::ARR_EXISTS_SKIP){
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
function array_grep($array, $exclude = array(), $flag = \DF\Helpers\Arr::ARR_USE_VALS){
    return \DF\Helpers\Arr::grep($array, $exclude, $flag);
}


/**
 * Flatten a multi-dimensional array into a single dimensional array
 * @param type $array
 * @param type $glue
 * @param type $reset
 * @return boolean
 */
function array_flatten($array, $glue = '', $reset = true){
    return \DF\Helpers\Arr::flatten($array, $glue, $reset);
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
function array_sort(&$array, $order, $sortBy = \DF\Helpers\Arr::ARR_SORT_BY_VALUE, $recursive = false){
    return \DF\Helpers\Arr::sort($array, $order, $sortBy, $recursive);
}

/**
 * Set a specific dot-notation element to a value and return the parent element
 * @param type $array
 * @param type $key
 * @param type $val
 * @return type
 */
function array_set(&$array, $key, $val){
    return \DF\Helpers\Arr::set($array, $key, $val);
}

/**
 * Total up the values of a given key in the array
 * @param array $array
 * @param type $key
 * @return type
 */
function array_total(array &$array, $key){
    return \DF\Helpers\Arr::total($array, $key);
}


/**
 * Insert a value into an indexed array
 * @param array $array
 * @param type $value
 * @param type $position
 * @return array
 * @throws \InvalidArgumentException
 */
function array_insert(array &$array, $value, $position = null){
    return DF\Helpers\Arr::insert($array, $value, $position);
}

/**
 * Check if an array has all of the keys specified
 * @param array $keys
 * @param array $array
 * @return boolean
 */
function array_has_keys(array $keys, array $array){
    return DF\Helpers\Arr::hasKeys($keys, $array);
}







// Object Helper Functions
function object_find_by_property($arrayOfObjects, $prop, $val){

  $return = array_filter($arrayOfObjects, function($el) use ($prop, $val){
    return (strtolower($el->$prop) === strtolower($val));
  });

  return (count($return) == 1) ? array_shift($return) : false;

}
