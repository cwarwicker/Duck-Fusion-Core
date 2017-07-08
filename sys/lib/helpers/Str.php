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
 * Strings
 * 
 * This Helper class provides various methods for working with strings.
 * Each method can also be accessed via a global function, defined in the common/Functions.php file
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

namespace DF\Helpers;

abstract class Str
{
    
    /**
    * Cut a string to a set number of characters, optionally appending something to the end, e.g. "This string is cu..."
    * @param type $str
    * @param type $length
    * @return type
    */
    public static function cut($str, $length, $append = ''){
        return ( strlen($str) > $length ) ? substr($str, 0, $length) . $append : $str;
    }
    
    
    /**
    * Generate a random string of a given (or random) length, using given (or default) characters
    * @param type $length
    * @param type $chars
    * @return type
    */
   public static function rand($length = false, $chars = false){

       $characters = "0123456789AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz";

       // If no length set, choose a random length
       if (!$length) $length = mt_rand(5, 255);

       // If we've specified chars, use them instead of the defaults
       if ($chars){
           if (is_string($chars)){
               $characters = $chars;
           } elseif (is_array($chars)){
               $characters = implode("", $chars);
           }
       }

       $l = strlen($characters);

       $return = "";

       for ($i = 0; $i < $length; $i++){
           $rand = mt_rand(0, ($l - 1));
           $return .= $characters[$rand];
       }

       return $return;


   }
   
   
   /**
    * Check if a string starts with specific characters
    * @param type $haystack
    * @param type $needle If an array is passed, it just has to match at least one of the elements
    * @return type
    */
    public static function begins($haystack, $needle)
    {

        if (is_array($needle))
        {
            $result = false;
            foreach($needle as $str)
            {
                $length = strlen($str);
                $strResult = ( strcasecmp(substr($haystack, 0, $length), $str) == 0 );
                $result = ($result || $strResult);
            }
            return $result;
        }
        else
        {
            $length = strlen($needle);
            return ( strcasecmp(substr($haystack, 0, $length), $needle) == 0 );
        }

    }
    
    /**
    * Check if a string ends with specific characters
    * @param type $haystack
    * @param type $needle  If an array is passed, it just has to match at least one of the elements
    * @return type
    */
    public static function ends($haystack, $needle)
    {
        if (is_array($needle))
        {
            $result = false;
            foreach($needle as $str)
            {
                $strResult = (strcasecmp(substr($haystack, -strlen($str)),$str) == 0);
                $result = ($result || $strResult);
            }
            return $result;
        }
        else
        {
            return (strcasecmp(substr($haystack, -strlen($needle)),$needle) == 0);
        }
    }
    
    
    /**
    * Check if a string contains another string
    * @param type $haystack
    * @param type $needle
    * @return type
    */
    public static function contains($haystack, $needle)
    {
        if (is_array($needle))
        {
            $result = false;
            foreach($needle as $str)
            {
                $strResult = (stripos($haystack, $str) !== false);
                $result = ($result || $strResult);
            }
            return $result;
        }
        else
        {
            return (stripos($haystack, $needle) !== false);
        }
    }
    
    /**
     * Increment a string, as you would a filename, e.g. "test" => "test_1". "test_67" => "test_68", etc...
     * @param type $str
     * @return type
     */
    public static function increment($str)
    {
        
        // See if there is already a number there
        preg_match("/.*?_(\d+)/", $str, $matches);
        $num = ($matches) ? ($matches[1] + 1) : 1;
        
        // If matches, replace it
        if ($matches){
            $str = preg_replace("/_(\d+)$/", "_{$num}", $str);
        }
        // otherwise just append it
        else {
            $str .= "_{$num}";
        }
        
        return $str;
        
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
     * 
     * @param type $str The string to cycle through, using the delim to split it
     * @param type $delim Default separator is a comma
     * @param type $id An id is only needed if you are for some reason calling cycle on the same string on the same line of the script, but want multiple versions of the results
     * @return type
     */
    public static function cycle($str, $delim = ',', $id = '')
    {
        
        $backtrace = debug_backtrace();
        $script = $backtrace[1]['file'] . ':' . $backtrace[1]['line'];
                        
        $array = explode($delim, $str);
        if ($array)
        {
            
            // Is this in the memory yet?
            $name = 'df_string_cycle-' . $str . '-' . $script;
            if (strlen($id) > 0){
                $name .= '-' . $id;
            }
                        
            if (isset($GLOBALS[$name])){
                $array = $GLOBALS[$name];
            } 
            
            // Take first element off array and put it on the end
            $el = array_shift($array);
            array_push($array, $el);
            
            // Save in memory for this execution of the script
            $GLOBALS[$name] = $array;
            
            // Return that first element
            return $el;
            
        }
        
        return $str;
        
    }
    
    
    
}



