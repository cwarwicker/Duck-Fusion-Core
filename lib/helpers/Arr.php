<?php

/**
 * Helper functions: Arrays
 * 
 * @copyright 16-Jun-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace DF\Helpers;


//more

abstract class Arr
{
    
    const ARR_EXISTS_SKIP = 0; # If an element in the array already exists with that key, do nothing
    const ARR_EXISTS_OVERWRITE = 1; # If an element in the array already exists with that key, overwrite it
    const ARR_EXISTS_APPEND = 2; # If an element in the array already exists with that key, convert it to an array and append the new value

    const ARR_USE_KEYS = 0;
    const ARR_USE_VALS = 1;

    const ARR_SORT_ASC = 'asc';
    const ARR_SORT_DESC = 'desc';
    const ARR_SORT_BY_VALUE = 'v';
    const ARR_SORT_BY_KEY = 'k';
    
    
    /**
     * Work out the average of all the elements in the array
     * @param array $array
     * @param \Closure $function If this is passed in, then if any elements in the array are arrays or objects, this Closure will be called to return the value you want from it.
     *        Need to make sure it returns FALSE if there is a problem, so the total and count are not incremented
     * @return type
     */
    public static function avg(array $array, \Closure $function = null){
        
        $ttl = 0;
        $cnt = 0;
        foreach($array as $arr){
            
            // If the element is an array itself or an object, call the Closure to get which element we want from it
            if (is_array($arr) || is_object($arr)){
                $result = call_user_func($function, $arr);
                if ($result !== false){
                    $ttl += $result;
                    $cnt++;
                }
            } elseif (is_numeric($arr)){
                $ttl += $arr;
                $cnt++;
            }
        }
                
        return ($ttl / $cnt);
        
    }
    
    
    /**
     * Find elements in a multidimensional array, using dot notation
     * Example:
     * 
     * Consider we had a multidimensional array with several levels, such as:
     * 
     * $array = array(

            'people' => array(

                'conn' => array(
                    'age' => 28,
                    'hair' => 'blond',
                    'sex' => 'male'
                ),
                'liz' => array(
                    'age' => 27,
                    'hair' => 'brown',
                    'sex' => 'female'
                )

            )

        );
     * 
     * Normally if you wanted to get say example the age of conn, you'd do something like: (isset($array['people']['conn']['age'])) ? $array['people']['conn']['age'] : false;
     * 
     * You can do the same thing here by using the find method and dot notation: \DF\Helpers\Array::find( $array, 'people.conn.age' );
     * 
     * @param array $array
     * @param type $find
     * @return boolean
     */
    public static function find(array $array, $find){

        $return = $array;
        
        $split = explode('.', $find);
        if ($split)
        {
            foreach($split as $key)
            {
                                
                if (is_array($return) && array_key_exists($key, $return))
                {
                    $return = $return[$key];
                }
                else
                {
                    return false;
                }
            }
        }

        return $return;

    }
    
    /**
     * Check if an array has a specified element, using dot notation
     * @param array $array
     * @param type $find
     * @return type
     */
    public static function has(array $array, $find){
        
        $result = self::find($array, $find);
        return ($result !== false);
        
    }


    /**
     * Get elements from an array, where they meet the requirements laid out in the Closure function
     * 
     * Example:
     * 
     *  $array = array(100, '200', 300, '400', 500);
     *
     *   var_dump( \DF\Helpers\Arr::where($array, function($k, $v){
     *       return ($v > 300);
     *   }) );
     * 
     * @param array $array
     * @param \Closure $function
     * @return type
     */
    public static function where(array $array, \Closure $function){

        $tmpArray = array();

        foreach($array as $key => $value){

            // If the value is itself an array, call it on that
            if (is_array($value)){
                $value = self::where($value, $function);
                if ($value){
                    $tmpArray[$key] = $value;
                }
            } else {

                // Count how many parameters in the Closure
                $reflection = new \ReflectionFunction($function);
                $cntParams = count($reflection->getParameters());
                
                // If 2 parameters, pass in key then value
                if ($cntParams == 2){
                    $result = call_user_func($function, $key, $value);
                }
                
                // Else
                else {
                    $result = call_user_func($function, $value);
                }
                
                // If result was true, keep this value in the returned array
                if ($result){
                    $tmpArray[$key] = $value;
                }

            }

        }

        return $tmpArray;

    }


    /**
     * Get the first element in the array to pass the callback test.
     * The callback requires both $key and $val arguments
     * If none pass it, you can use a default parameter to return that as the default value
     * @param type $array
     * @param type $function
     * @param type $default
     */
    public static function first(array $array, \Closure $function, $default = false){

        foreach($array as $key => $value){

            if (call_user_func($function, $key, $value) ){
                return $value;
            }

        }

        // If none have passed return default, which will return false anyway if one hasn't been specified
        return $default;

    }

    /**
     * Get the last element in the array to pass the callback test
     * If none pass it, you can use a default parameter to return that as the default value
     * @param type $array
     * @param type $function
     * @param type $default
     */
    public static function last(array $array, \Closure $function, $default = false){
        return self::first( array_reverse($array), $function, $default );
    }


    /**
     * Add a key => val relationship onto an existing array. If such an relationship already exists, the flag option will define what we should do
     * Uses dot notation to find the element, like in the Array::find method
     * 
     * Examples:
     * 
     *  var_dump( \DF\Helpers\Arr::add( $array, 'new', 'new one' ) );
        var_dump( \DF\Helpers\Arr::add( $array, 'people.liz.hair', 'pink', self::ARR_EXISTS_APPEND ) );
        var_dump( \DF\Helpers\Arr::add( $array, 'people.conn.age', 18 ) );
        var_dump( \DF\Helpers\Arr::add( $array, 'people', 'test' ) );
        var_dump( \DF\Helpers\Arr::add( $array, 'people.conn.age', 38 ) );
     * 
     * @param type $array
     * @param type $key
     * @param type $val 
     */
    public static function add(&$array, $key, $val = null, $flag = self::ARR_EXISTS_OVERWRITE){

        // If the value is null, then the $key is actually the value and it's not multidimensional, so just append it
        if (is_null($val)){
            $array[] = $key;
            return $array;
        }
                
        // Use the key to get the current value, using dot notation
        $el = self::find($array, $key);
        
        // Not set, so just set it
        if ($el === false){
            
            // Set the value
            self::set($array, $key, $val);
            return $array;
            
        }
        
        // Otherwise that element must already exist, so what do we want to do with it?
        switch($flag)
        {
            
            case self::ARR_EXISTS_APPEND:
                
                // If not an array, make it into an array
                if (!is_array($el)){
                    $el = array( $el );
                }
                
                // Append to it
                array_push($el, $val);
                
                // Set the array as the value
                self::set($array, $key, $el);
                
                return $array;
                
            break;
            
            case self::ARR_EXISTS_OVERWRITE:
                
                // Just set it normally as if it didn't exist
                self::set($array, $key, $val);
                return $array;
                
            break;
        
            case self::ARR_EXISTS_SKIP:
            default:
                // Do nothing
                return $array;
            break;
        
        }
        
    }
    
    /**
     * Set a given element within an array to a specific value, using dot notation to move down through the sub elements of the keys
     * It's essentially the same as doing an Arr::add($array, $key, $val, Arr::ARR_EXISTS_OVERWRITE) except it returns the parent of the element you updated, instead of the whole array
     * @param type $array
     * @param type $key
     * @param type $val
     * @return type
     */
    public static function set(&$array, $key, $val){
        
        $keys = explode('.', $key);
        while (count($keys) > 1)
        {
            // Get the first key in the dot notation
            $key = array_shift($keys);

            // If this key doesn't exist at all, set it to blank array
            if (!array_key_exists($key, $array) || !is_array($array[$key]))
            {
                $array[$key] = array();
            }

            // Set the $array variable to now be the current level we have got to, instead of the whole array
            // This is a bit confusing, but basically the $array variable is now a reference to a sub element of the original $array variable
            // So at the end of this method when we return $array, the value actually returned by this Array::set method will be only that last element in the array we have entered
            // However, since the $array variable in this method is now a reference, wherever we called this from, e.g. Array::add the $array variable there will still be the original array
            // but with the new element added, because it was passed in via reference to this method
            $array =& $array[$key];

        }

        // Get the first key again - This should be the last one left after we went through all the previous levels in the dot notation
        $key = array_shift($keys);
        $array[$key] = $val;
        
        // This will return the new element, not the final array - that will be updated in the source of the $array variable since it was passed in by reference
        return $array;
        
    }

    /**
     * Return two seperate arrays, one of keys, one of values
     * !!!!I'm not sure if this one works as I intended it, I can't remember!!!!
     * @param array $array 
     * @param bool $recursive If this is true, then if the value is itself an array, it will run array_split over that as well, and any sub-arrays beyond that
     */
    public static function split(&$array, $recursive = false){

        $keys = array();
        $vals = array();

        foreach($array as $k => $v){
            $keys[] = $k;
            if ($recursive && is_array($v)){
                $v = self::split($v, $recursive);
            }
            $vals[] = $v;
        }

        return array("keys" => $keys, "vals" => $vals);

    }

    /**
     * Return an array, excluding any elements with the keys/values as defined
     * @param type $array
     * @param type $exclude 
     */
    public static function grep($array, $exclude = array(), $flag = self::ARR_USE_VALS){

        $tmpArray = array();
        
        if (!empty($array) && !empty($exclude) ){

            foreach($array as $k => $v){

                // If the value is an array, we'll have to run that array through this same function
                if (is_array($v)){
                    $v = self::grep($v, $exclude, $flag);
                }

                switch($flag)
                {

                    // Key match and case insensitive
                    case (self::ARR_USE_KEYS):
                        if (!in_array($k, $exclude, true)){
                            $tmpArray[$k] = $v;
                        }
                    break;

                    // Value match and case insensitive
                    case (self::ARR_USE_VALS):
                    default:
                        if (!in_array($v, $exclude, true)){
                            $tmpArray[$k] = $v;
                        }
                    break;

                }


            }

            return $tmpArray;

        }

        return $array;

    }

    /**
     * Flatten a multi-dimensional array into a single dimensional array
     * @staticvar array $return
     * @staticvar array $curr_key
     * @param type $array
     * @param type $glue
     * @param type $reset
     * @return boolean
     */
    public static function flatten($array, $glue = '', $reset = true) { 

        if (!is_array($array)){
            return false;
        }

        static $return = array();
        static $curr_key = array();

        if ($reset){
            $return = array();
            $curr_key = null;
        }

        $isGluing = (strlen($glue) > 0);

        foreach($array as $key => $val){

            $curr_key[] = $key;

            // If the element itself is an array, flatten that first
            if (is_array($val)){
                self::flatten($val, $glue, false);
            } else {

                // If we have specified a glue string, use that along with all the keys so far as the key, otherwise just append numerically
                if ($isGluing){
                    $return[implode($glue, $curr_key)] = $val;
                } else {
                    $return[] = $val;
                }

            }

            array_pop($curr_key);

        }

        return $return;

    }

    /**
     * Check if an array is multidimensional
     * @param type $array
     * @return boolean
     */
    public static function isMulti($array){
        
        if (!is_array($array)) { 
            return false; 
        } 
        
        $elements = array_filter($array, 'is_array');
        return (count($elements) > 0);
        
    }
    
    /**
     * Sort an array in a given direction
     * @param type $array
     * @param type $order
     * @param type $sortBy ARR_SORT_BY_VALUE or ARR_SORT_BY_KEY
     * @return boolean
     */
    public static function sort(&$array, $order, $sortBy = self::ARR_SORT_BY_VALUE){
        
        if (!is_array($array)){
           return false;
        }
       
        switch($order)
        {
            case self::ARR_SORT_ASC:
                switch($sortBy)
                {
                    case self::ARR_SORT_BY_VALUE:
                        asort($array, SORT_NATURAL);
                    break;
                    case self::ARR_SORT_BY_KEY:
                        ksort($array, SORT_NATURAL);
                    break;
                }
            break;
            case self::ARR_SORT_DESC:
                switch($sortBy)
                {
                    case self::ARR_SORT_BY_VALUE:
                        arsort($array, SORT_NATURAL);
                    break;
                    case self::ARR_SORT_BY_KEY:
                        krsort($array, SORT_NATURAL);
                    break;
                }
            break;
            
        }
       
        return $array;
        
    }
    
    
}










