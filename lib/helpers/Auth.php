<?php

/**
 * Authentication helper class
 * 
 * Contains methods for hashing passwords, comparing passwords, etc...
 *  * 
 * @copyright 21-Jun-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace DF\Helpers;

class Auth
{
    
    const DEFAULT_METHOD = 'sha512';
    
    protected $password;
    protected $salt = null;
    protected $usePepper = true;
    protected $method = self::DEFAULT_METHOD;
    
    protected $db = null;






    public function __construct(){
        
        global $db;
        $this->db = $db;
        
    }
    
    public function setPassword($val){
        $this->password = $val;
        return $this;
    }
    
    public function getPassword(){
        return $this->password;
    }
    
    public function setSalt($salt = null){
        $this->salt = $salt;
        return $this;
    }
    
    public function getSalt(){
        return $this->salt;
    }
    
    public function usePepper($val){
        if (is_bool($val)){
            $this->usePepper = $val;
        }
        return $this;
    }
    
    public function setMethod($method){
        $this->method = $method;
        return $this;
    }
    
    public function getMethod(){
        return $this->method;
    }
    
    public function login($ident, $password){
        
        global $cfg;
        
        // Set the password into the auth object for later use
        $this->password = $password;
                
        $return = array('result' => false);
        
        // Check if configuration details are stored to enable us to try and authenticate
        if (!is_null(@$cfg->config->user_table) && !is_null(@$cfg->config->user_identfield)){
                        
            // First see if the user exists at all
            $uID = $this->getUID( $ident );
            if (!$uID){
                $return['message'] = \df_string('errors:invalidlogin');
                return $return;
            }
            
            // Get the full user record
            $user = $this->getUser($uID);
            
            // Check it's not been deleted
            if ($user->deleted != 0){
                $return['message'] = \df_string('errors:invalidlogin');
                return $return;
            }
            
            // Set the user salt, if we have/want one
            if ($this->salt !== false && isset($user->salt)){
                $this->setSalt($user->salt);
            }
                        
            // We know that the user exists with that ident, so now let's check if the password matches
            if (!$this->compare($user->password)){
                $return['message'] = \df_string('errors:invalidlogin');
                return $return;
            }
            
            // Now check if the user has been confirmed
            if ($user->confirmed != 1){
                $return['message'] = \df_string('errors:userunconfirmed');
                return $return;
            }
            
            // At this point, everything should be ok, so set the user in a session
            
                        
        } else {
            $return['message'] = \df_string('errors:invaliduserconfig');
        }
        
        return $return;
        
    }
    
    /**
     * Get a user record from its id
     * @global \DF\Helpers\type $cfg
     * @param type $id
     * @return boolean
     */
    protected function getUser($id){
        
        global $cfg;
        
        $user = $this->db->select($cfg->config->user_table, array('id' => $id));
        if (!$user){
            return false;
        }
        
        $user->_uid = $id;
        return $user;
        
    }
    
    /**
     * Get the id of a user record from its ident
     * @global type $cfg
     * @param type $ident
     * @return type
     */
    protected function getUID($ident){
        
        global $cfg;
        
        $user = $this->db->select($cfg->config->user_table, array($cfg->config->user_identfield => $ident), null, 'id');
        return ($user) ? $user->id : false;
        
    }
    
    
    
    
    
    
    
    
    /**
     * Hashes a specified password (setPassword) with a specified algorithm (setMethod or use the default)
     * Returns a hashed string on success, or FALSE on error
     * @return mixed
     * @throws \DF\DFException
     */
    public function hash(){
        
        if (!$this->password || !$this->method){
            return false;
        }
        
        $password = $this->password;
        
        $methods = hash_algos();
        if (!in_array($this->method, $methods)){
            throw new \DF\DFException(df_string("authentication"), df_string("errors:invalidhashmethod"));
            return false;
        }
        
        // Salt - If null, that means generate a random one, if false that means don't use one, otherwise use whatever is supplied (if it's a string)
        if (is_null($this->salt)){
            $this->salt = \DF\Helpers\Strings::rand( mt_rand(8, 16) );
            $password .= $this->salt;
        } elseif (is_string($this->salt) && strlen($this->salt) > 0){
            $password .= $this->salt;
        }
        
        // Pepper
        if (strlen($this->salt) > 0 && $this->usePepper === true){
            
            // Take the first and last letter of the salt and add them to the front of the password string
            $letters = array();
            $letters[0] = substr($this->salt, 0, 1);
            $letters[1] = substr($this->salt, -1, 1);
            $password = $letters[0] . $letters[1] . $password;
            
        }
                
        // Now hash it
        return hash($this->method, $password);
        
    }
    
    /**
     * Hash a password using the specified password, salt and method and then compare it to a given hash (most likely from your users database table)
     * @param type $dbHash
     * @return boolean
     */
    public function compare($dbHash){
                        
        $hash = $this->hash();
        if (!$hash){
            return false;
        }
                
        return ($hash === $dbHash);
        
    }
    
        
    /**
     * Reset the properties on the object
     */
    public function reset(){
        $this->password = null;
        $this->salt = null;
        $this->usePepper = true;
        $this->method = self::DEFAULT_METHOD;
    }
    
}