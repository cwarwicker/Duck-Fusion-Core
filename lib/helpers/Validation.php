<?php
/**
 * THINK ABOUT REDOING THIS
 * or at least will need some changes
 */


namespace DF\Helpers;

// Define constants of supported validation rules

// Something must be passed to the field's value, other than empty string, null, false, etc...
if (!defined('DF_VALIDATION_RULE_REQUIRED')) define('DF_VALIDATION_RULE_REQUIRED', 'REQ');

// The field must be required if another field is set with a value
if (!defined('DF_VALIDATION_RULE_REQUIRED_FRIEND')) define('DF_VALIDATION_RULE_REQUIRED_FRIEND', 'REQ_FRIEND');

// The field must be at least this many characters long
if (!defined('DF_VALIDATION_RULE_MIN_LENGTH')) define('DF_VALIDATION_RULE_MIN_LENGTH', 'MIN_LEN');

// The field must be at most this many characters long
if (!defined('DF_VALIDATION_RULE_MAX_LENGTH')) define('DF_VALIDATION_RULE_MAX_LENGTH', 'MAX_LEN');

// The field must be exactly this many characters long
if (!defined('DF_VALIDATION_RULE_EXACT_LENGTH')) define('DF_VALIDATION_RULE_EXACT_LENGTH', 'LEN');

// The field must be a valid date, in a specified format
if (!defined('DF_VALIDATION_RULE_DATE')) define('DF_VALIDATION_RULE_DATE', 'DATE');

// The field must be a valid email
if (!defined('DF_VALIDATION_RULE_EMAIL')) define('DF_VALIDATION_RULE_EMAIL', 'EMAIL');

// The field must be a valid url
if (!defined('DF_VALIDATION_RULE_URL')) define('DF_VALIDATION_RULE_URL', 'URL');

// The field must be a valid number
if (!defined('DF_VALIDATION_RULE_NUMBER')) define('DF_VALIDATION_RULE_NUMBER', 'NUMBER');

// The field must be a number greater than x
if (!defined('DF_VALIDATION_RULE_NUMBER_GREATER_THAN')) define('DF_VALIDATION_RULE_NUMBER_GREATER_THAN', 'NUMBER_GT');

// The field must be a string and must meet the specified parameters
if (!defined('DF_VALIDATION_RULE_STRING')) define('DF_VALIDATION_RULE_STRING', 'STR');

    // String allows: Alpha
    if (!defined('DF_VALIDATION_RULE_STRING_ALPHA')) define('DF_VALIDATION_RULE_STRING_ALPHA', 'STR_ALPHA');

    // String allows: Numeric
    if (!defined('DF_VALIDATION_RULE_STRING_NUM')) define('DF_VALIDATION_RULE_STRING_NUM', 'STR_NUM');
    
    // String allows: Whitespace
    if (!defined('DF_VALIDATION_RULE_STRING_SPACE')) define('DF_VALIDATION_RULE_STRING_SPACE', 'STR_SPACE');

    // String allows: Quotes
    if (!defined('DF_VALIDATION_RULE_STRING_QUOTES')) define('DF_VALIDATION_RULE_STRING_QUOTES', 'STR_QUOTES');


    // String allows: Underscores & Hyphens
    if (!defined('DF_VALIDATION_RULE_STRING_UNDER')) define('DF_VALIDATION_RULE_STRING_UNDER', 'STR_UNDER');


    // String allows: Punctuation: Spaces, Quotes, Commas, Fullstops, Question marks, Exclamation marks, etc...
    if (!defined('DF_VALIDATION_RULE_STRING_PUNC')) define('DF_VALIDATION_RULE_STRING_PUNC', 'STR_PUNC');


    // String allows: Newline/Carriage return
    if (!defined('DF_VALIDATION_RULE_STRING_NEWLINE')) define('DF_VALIDATION_RULE_STRING_NEWLINE', 'STR_NEWLINE');



// The field must match a given regular expression
if (!defined('DF_VALIDATION_RULE_REGEX')) define('DF_VALIDATION_RULE_REGEX', 'REGEX');
    
// The valur given must be of a given type
if (!defined('DF_VALIDATION_RULE_TYPE')) define('DF_VALIDATION_RULE_TYPE', 'TYPE');

// The file must be a valid image
if (!defined('DF_VALIDATION_RULE_IMAGE')) define('DF_VALIDATION_RULE_IMAGE', 'IMAGE');


/**
 * User-input validation class. Used to validate that form elements or other user-input data meets your requirements
 * 
 * @copyright 16-Jun-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

// >>DFTODO change it so all validation rules have their own methods, so we can extend this class and add more if we want




class Validation {
    
    private $fields = array();
    private $errors = array();
    
    public function __construct() {
        ;
    }
    
    public function getErrors(){
        return $this->errors;
    }

    public function addField($field, $value, $msg = null){
        
        $obj = new ValidationField($field, $value, $msg);
        $this->fields[] = &$obj;
        return $obj;
        
    }
    
    public function addError($msg){
        $this->errors[] = $msg;
        return $this;
    }
    
    /**
     * Validate all the fields we have added
     */
    public function validate(){
        
        // If not fields added, pass
        if (empty($this->fields)) return true;
        
        $errors = 0;
        
        // Loop fields and validate them
        foreach($this->fields as $field){
            
            if (!$field->validate()){
                foreach($field->getErrors() as $fieldError){
                    $this->errors[] = $fieldError;
                }
                $errors++;
            }
            
        }
        
        return ($errors == 0) ? true : false;
        
    }
    
}

/**
 * A field that we want to validate against a set of rules
 */
class ValidationField {
    
    private $name;
    private $value;
    private $msg;
    private $rules = array();
    private $errors = array();
    private $isValid = false;
    
    public function __construct($field, $value, $msg){
        $this->name = $field;
        $this->value = $value;
        $this->msg = $msg;
        return $this;
    }
    
    public function getValue(){
        return $this->value;
    }
    
    /**
     * Get the error mesages if there are any
     * @return type
     */
    public function getErrors(){
        return $this->errors;
    }
    
    /**
     * Add a rule that this field must pass in order to validate successfully
     * @param type $rule
     * @param type $args
     */
    public function addRule($rule, $args = null){
        
        $this->rules[] = array($rule, $args);
        return $this;
        
    }
        
    /**
     * Validate this field against any rules that have been set
     */
    public function validate(){
        
        // If no rules have been set we can't validate against anything
        if (empty($this->rules)){
            $this->isValid = true;
            return true;
        }
        
        $errors = 0;
        
        foreach($this->rules as $rule){
            
            $type = $rule[0];
            $args = $rule[1];
            
            // Switch the type to do whatever validation is required
            switch($type)
            {
                
                case DF_VALIDATION_RULE_DATE:
                    
                    // Data types accepted: String
                    // Argument types accepted: String (format) [Any of the formats accepted by PHP's DateTime class]
                    $format = $args;
                    
                    $date = \DateTime::createFromFormat($format, $this->value);
                    
                    if ($date === false){
                        $errors++;
                        $failMsg = df_string('validation:failed');
                            $failMsg = str_replace('%field%', $this->name, $failMsg);
                        $msg = df_string('validation:date');
                        if (!is_null($this->msg)){
                            $this->errors[] = $this->msg;
                        } else {
                            $this->errors[] = $failMsg . ': ' . $msg;
                        }
                    }
                    
                break;
            
                case DF_VALIDATION_RULE_EMAIL:
                    
                    // Data types accepted: String
                    // Argument types accepted: null
                    
                    $filter = filter_var($this->value, FILTER_VALIDATE_EMAIL);
                    
                    if ($filter === false){
                        $errors++;
                        $failMsg = df_string('validation:failed');
                            $failMsg = str_replace('%field%', $this->name, $failMsg);
                        $msg = df_string('validation:email');
                        if (!is_null($this->msg)){
                            $this->errors[] = $this->msg;
                        } else {
                            $this->errors[] = $failMsg . ': ' . $msg;
                        }
                    }
                    else {
                        // Set the value to the filtered value
                        $this->value = $filter;
                    }
                    
                break;
            
                case DF_VALIDATION_RULE_EXACT_LENGTH:
                    
                    // Data types accepted: String, Array
                    // Argument types accepted: int
                    
                    $len = (int)$args;
                    
                    if (is_array($this->value)){
                        
                        $cnt = count($this->value);
                        
                        if ($cnt <> $len){
                            $errors++;
                            $failMsg = df_string('validation:failed');
                                $failMsg = str_replace('%field%', $this->name, $failMsg);
                            $msg = df_string('validation:exactlen:array');
                                $msg = str_replace('%len%', $len, $msg);
                                $msg = str_replace('%num%', $cnt, $msg);
                            if (!is_null($this->msg)){
                                $this->errors[] = $this->msg;
                            } else {
                                $this->errors[] = $failMsg . ': ' . $msg;
                            }
                        }
                        
                    }
                    elseif (is_string($this->value)){
                        
                        $this->value = trim($this->value);
                        $cnt = strlen($this->value);
                        
                        if ($cnt <> $len){
                            $errors++;
                            $failMsg = df_string('validation:failed');
                                $failMsg = str_replace('%field%', $this->name, $failMsg);
                            $msg = df_string('validation:exactlen:string');
                                $msg = str_replace('%len%', $len, $msg);
                                $msg = str_replace('%num%', $cnt, $msg);
                            if (!is_null($this->msg)){
                                $this->errors[] = $this->msg;
                            } else {
                                $this->errors[] = $failMsg . ': ' . $msg;
                            }
                        }
                        
                    }
                    else {
                        
                        $errors++;
                        $failMsg = df_string('validation:failed');
                            $failMsg = str_replace('%field%', $this->name, $failMsg);
                        $msg = df_string('validation:unexpectedvaluedatatype');
                            $msg = str_replace('%datatype%', gettype($this->value), $msg);
                            $msg = str_replace('%expected%', 'String, Array', $msg);
                        if (!is_null($this->msg)){
                            $this->errors[] = $this->msg;
                        } else {
                            $this->errors[] = $failMsg . ': ' . $msg;
                        }
                    }
                    
                break;
            
                case DF_VALIDATION_RULE_MAX_LENGTH:
                    
                    // Data types accepted: String, Array
                    // Argument types accepted: int
                    
                    $max = (int)$args;
                    
                    if (is_array($this->value)){
                        
                        $cnt = count($this->value);
                        
                        if ($cnt > $max){
                            $errors++;
                            $failMsg = df_string('validation:failed');
                                $failMsg = str_replace('%field%', $this->name, $failMsg);
                            $msg = df_string('validation:maxlen:array');
                                $msg = str_replace('%max%', $max, $msg);
                                $msg = str_replace('%num%', $cnt, $msg);
                            if (!is_null($this->msg)){
                                $this->errors[] = $this->msg;
                            } else {
                                $this->errors[] = $failMsg . ': ' . $msg;
                            }
                        }
                        
                    }
                    elseif (is_string($this->value)){
                        
                        // Trim whitespace from beginning and end
                        $this->value = trim($this->value);
                        $cnt = strlen($this->value);
                        
                        if ($cnt > $max){
                            $errors++;
                            $failMsg = df_string('validation:failed');
                                $failMsg = str_replace('%field%', $this->name, $failMsg);
                            $msg = df_string('validation:maxlen:string');
                                $msg = str_replace('%max%', $max, $msg);
                                $msg = str_replace('%num%', $cnt, $msg);
                            if (!is_null($this->msg)){
                                $this->errors[] = $this->msg;
                            } else {
                                $this->errors[] = $failMsg . ': ' . $msg;
                            }
                        }
                        
                    }
                    else {
                        
                        $errors++;
                        $failMsg = df_string('validation:failed');
                            $failMsg = str_replace('%field%', $this->name, $failMsg);
                        $msg = df_string('validation:unexpectedvaluedatatype');
                            $msg = str_replace('%datatype%', gettype($this->value), $msg);
                            $msg = str_replace('%expected%', 'String, Array', $msg);
                        if (!is_null($this->msg)){
                            $this->errors[] = $this->msg;
                        } else {
                            $this->errors[] = $failMsg . ': ' . $msg;
                        }
                    }
                    
                break;
            
                case DF_VALIDATION_RULE_MIN_LENGTH:
                    
                    // Data types accepted: String, Array
                    // Argument types accepted: int
                    
                    $min = (int)$args;
                    
                    if (is_array($this->value)){
                        
                        $cnt = count($this->value);
                        
                        if ($cnt < $min){
                            $errors++;
                            $failMsg = df_string('validation:failed');
                                $failMsg = str_replace('%field%', $this->name, $failMsg);
                            $msg = df_string('validation:minlen:array');
                                $msg = str_replace('%min%', $min, $msg);
                                $msg = str_replace('%num%', $cnt, $msg);
                            if (!is_null($this->msg)){
                                $this->errors[] = $this->msg;
                            } else {
                                $this->errors[] = $failMsg . ': ' . $msg;
                            }
                        }
                        
                    }
                    elseif (is_string($this->value)){
                        
                        $this->value = trim($this->value);
                        $cnt = strlen($this->value);
                        
                        if ($cnt < $min){
                            $errors++;
                            $failMsg = df_string('validation:failed');
                                $failMsg = str_replace('%field%', $this->name, $failMsg);
                            $msg = df_string('validation:minlen:string');
                                $msg = str_replace('%min%', $min, $msg);
                                $msg = str_replace('%num%', $cnt, $msg);
                            if (!is_null($this->msg)){
                                $this->errors[] = $this->msg;
                            } else {
                                $this->errors[] = $failMsg . ': ' . $msg;
                            }
                        }
                        
                    }
                    else {
                        
                        $errors++;
                        $failMsg = df_string('validation:failed');
                            $failMsg = str_replace('%field%', $this->name, $failMsg);
                        $msg = df_string('validation:unexpectedvaluedatatype');
                            $msg = str_replace('%datatype%', gettype($this->value), $msg);
                            $msg = str_replace('%expected%', 'String, Array', $msg);
                        if (!is_null($this->msg)){
                            $this->errors[] = $this->msg;
                        } else {
                            $this->errors[] = $failMsg . ': ' . $msg;
                        }
                    }
                    
                break;
            
                case DF_VALIDATION_RULE_REGEX:
                    
                    // Data types accepted: String
                    // Argument types accepted: String (Regular Expresion)
                    
                    $pattern = $args;
                    
                    if (!preg_match($pattern, $this->value)){
                        
                        $errors++;
                        $failMsg = df_string('validation:failed');
                            $failMsg = str_replace('%field%', $this->name, $failMsg);
                        $msg = df_string('validation:regex');
                        if (!is_null($this->msg)){
                            $this->errors[] = $this->msg;
                        } else {
                            $this->errors[] = $failMsg . ': ' . $msg;
                        }
                        
                    }
                    
                break;
            
                case DF_VALIDATION_RULE_REQUIRED:
                    
                    // Data types accepted: Any
                    // Argument types accepted: null
                    
                    // Just make sure the value isn't null, isn't false and isn't an empty string - Basically just must have a value
                    if (is_string($this->value)) $this->value = trim($this->value);
                    
                    if (is_null($this->value) || $this->value === false || $this->value == '' || (is_array($this->value) && empty($this->value))){
                        
                        $errors++;
                        $failMsg = df_string('validation:failed');
                            $failMsg = str_replace('%field%', $this->name, $failMsg);
                        $msg = df_string('validation:req');
                        if (!is_null($this->msg)){
                            $this->errors[] = $this->msg;
                        } else {
                            $this->errors[] = $failMsg . ': ' . $msg;
                        }
                        
                    }
                    
                    
                break;
            
//                case DF_VALIDATION_RULE_REQUIRED_FRIEND:
//                    
//                break;
            
                case DF_VALIDATION_RULE_TYPE:
                    
                    // Data types accepted: Any
                    // Argument types accepted: String (Data type, not instancesof)
                    $type = $args;
                    
                    if (gettype($this->value) != $type){
                        
                        $errors++;
                        $failMsg = df_string('validation:failed');
                            $failMsg = str_replace('%field%', $this->name, $failMsg);
                        $msg = df_string('validation:type');
                            $msg = str_replace('%type%', $type, $msg);
                            $msg = str_replace('%found%', gettype($this->value), $msg);
                        if (!is_null($this->msg)){
                            $this->errors[] = $this->msg;
                        } else {
                            $this->errors[] = $failMsg . ': ' . $msg;
                        }
                        
                    }
                    
                break;
            
                case DF_VALIDATION_RULE_URL:
                    
                    // Data types accepted: String
                    // Argument types accepted: null
                    
                    $filter = filter_var($this->value, FILTER_VALIDATE_URL);
                    
                    if ($filter === false){
                        $errors++;
                        $failMsg = df_string('validation:failed');
                            $failMsg = str_replace('%field%', $this->name, $failMsg);
                        $msg = df_string('validation:url');
                        if (!is_null($this->msg)){
                            $this->errors[] = $this->msg;
                        } else {
                            $this->errors[] = $failMsg . ': ' . $msg;
                        }
                    }
                    else {
                        // Set the value to the filtered value
                        $this->value = $filter;
                    }
                    
                break;
                
                case DF_VALIDATION_RULE_NUMBER:
                    
                    if (!is_numeric($this->value) && !ctype_digit($this->value)){
                        $errors++;
                        $failMsg = df_string('validation:failed');
                            $failMsg = str_replace('%field%', $this->name, $failMsg);
                        $msg = df_string('validation:number');
                        if (!is_null($this->msg)){
                            $this->errors[] = $this->msg;
                        } else {
                            $this->errors[] = $failMsg . ': ' . $msg;
                        }
                    }
                    
                break;
                
                case DF_VALIDATION_RULE_NUMBER_GREATER_THAN:
                    
                    $num = $args;
                    
                    if ( (!is_numeric($this->value) && !ctype_digit($this->value)) || $this->value <= $num ){
                        
                        $errors++;
                        $failMsg = df_string('validation:failed');
                            $failMsg = str_replace('%field%', $this->name, $failMsg);
                        $msg = df_string('validation:numbergt');
                            $msg = str_replace('%num%', $num, $msg);
                        if (!is_null($this->msg)){
                            $this->errors[] = $this->msg;
                        } else {
                            $this->errors[] = $failMsg . ': ' . $msg;
                        }
                        
                    }
                    
                break;
            
                case DF_VALIDATION_RULE_STRING:
                    
                    // Data types accepted: String
                    // Argument types accepted: Array
                    
                    if (!is_string($this->value)){
                        
                        $errors++;
                        $failMsg = df_string('validation:failed');
                            $failMsg = str_replace('%field%', $this->name, $failMsg);
                        $msg = df_string('validation:unexpectedvaluedatatype');
                            $msg = str_replace('%datatype%', gettype($this->value), $msg);
                            $msg = str_replace('%expected%', 'String', $msg);
                        $this->errors[] = $failMsg . ': ' . $msg;
                    }
                    
                    if (!is_array($args)){
                        
                        $errors++;
                        $failMsg = df_string('validation:failed');
                            $failMsg = str_replace('%field%', $this->name, $failMsg);
                        $msg = df_string('validation:unexpectedargdatatype');
                            $msg = str_replace('%datatype%', gettype($args), $msg);
                            $msg = str_replace('%expected%', 'Array', $msg);
                        $this->errors[] = $failMsg . ': ' . $msg;
                    }
                    
                    // Loop through each string argument and test them all
                    if ($errors == 0){
                        
                        foreach($args as $arg){
                            
                            switch($arg)
                            {
                                
                                // >>DFTODO STR validations
                                
                            }
                            
                        }
                        
                    }
                    
                break;
                
                case DF_VALIDATION_RULE_IMAGE:
                    
                    $fInfo = new \finfo(FILEINFO_MIME_TYPE);
                    $type = $fInfo->file($this->value);
                    
                    $array = array(
                        'image/jpeg',
                        'image/pjpeg',
                        'image/gif',
                        'image/png',
                        'image/tiff',
                        'image/bmp'
                    );
                    
                    if (!in_array($type, $array)){
                        
                        $errors++;
                        
                        $failMsg = df_string('validation:failed');
                            $failMsg = str_replace('%field%', $this->name, $failMsg);
                        $msg = df_string('validation:image');
                        if (!is_null($this->msg)){
                            $this->errors[] = $this->msg;
                        } else {
                            $this->errors[] = $failMsg . ': ' . $msg;
                        }
                        
                        
                    }
                    
                    
                break;
            
                default:
                    
                break;
                
            }
            
        }
        
        if (!$this->errors) $this->isValid = true;
        
        return (!$this->errors) ? true : false;     
        
    }
    
}


//
//class ValidationException extends \Exception
//{
//    
//    private $fieldname;
//    private $type;
//    private $value;
//    private $args;
//    
//    public function __construct($message, $fieldname, $type, $value, $args) {
//        parent::__construct($message);
//        
//        $this->fieldname = $fieldname;
//        $this->type = $type;
//        $this->value = $value;
//        $this->args = $args;
//        
//    }
//    
//    /**
//     * Get the exception message
//     */
//    public function getException(){
//        
//        $output = "";
//        
//        $msg = df_string('validation:failed');
//        $msg = str_replace('%field%', $this->fieldname, $msg);
//        $msg = str_replace('%type%', $this->type, $msg);
//        
//        $output .= $msg;
//        $output .= "<br><br>";
//        $output .= $this->message;
//        $output .= "<br><br>";
//        
//        return $output;
//        
//    }
//    
//}