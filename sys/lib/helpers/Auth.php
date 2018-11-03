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
 * Authentication
 *
 * This Helper class provides various methods for working with authentication, such as password hashing, comparison, salting, etc...
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

namespace DF\Helpers;

class Auth
{

    const DEFAULT_METHOD = PASSWORD_ARGON2I;

    protected $password = null;
    protected $method = self::DEFAULT_METHOD;
    protected $db = null;

    protected $table = null;
    protected $ident_field = null;
    protected $session_key = null;
    protected $alsoCheck = null;

    public function __construct(){

        global $cfg, $db;
        $this->db = $db;

        if (isset($cfg->config->user_table) && !is_null($cfg->config->user_table)){
          $this->table = $cfg->config->user_table;
        }

        if (isset($cfg->config->user_identfield) && !is_null($cfg->config->user_identfield)){
          $this->ident_field = $cfg->config->user_identfield;
        }

        if (isset($cfg->config->session_name, $cfg->config->site_token)){
          $this->session_key = $cfg->config->session_name . '__' . $cfg->config->site_token;
        }

    }

    public function setPassword($val){
        $this->password = trim($val);
        return $this;
    }

    public function getPassword(){
        return $this->password;
    }

    public function setMethod($method){
        $this->method = $method;
        return $this;
    }

    public function getMethod(){
        return $this->method;
    }

    public function useTable($table){
      $this->table = $table;
      return $this;
    }

    public function useIdentField($field){
      $this->ident_field = $field;
      return $this;
    }

    public function useSessionKey($key){
      $this->session_key = $key;
      return $this;
    }

    public function alsoCheck(array $alsoCheckArray){
      $this->alsoCheck = $alsoCheckArray;
      return $this;
    }

    /**
     *
     * @global \DF\Helpers\type $cfg
     * @param type $ident
     * @param type $password
     * @return type
     */
    public function login($ident, $password){

        global $cfg;

        // Set the password into the auth object for later use
        $this->password = $password;

        $return = array('result' => false);

        // Check if configuration details are stored to enable us to try and authenticate
        if (!is_null($this->table) && !is_null($this->ident_field)){

            // First see if the user exists at all
            $uID = $this->getUID( $ident );
            if (!$uID){
                $return['message'] = \df_string('errors:invalidlogin');
                return $return;
            }

            // Get the full user record
            $user = $this->getUser($uID);

            // Check it's not been deleted
            if (isset($user->deleted) && $user->deleted != 0){
                $return['message'] = \df_string('errors:invalidlogin');
                return $return;
            }

            // We know that the user exists with that ident, so now let's check if the password matches
            if (!$this->compare($user->password)){
                $return['message'] = \df_string('errors:invalidlogin');
                return $return;
            }

            // Now check if the user has been confirmed
            if (isset($user->confirmed) && $user->confirmed != 1){
                $return['message'] = \df_string('errors:userunconfirmed');
                return $return;
            }

            // Were there any other fields we needed to check?
            if (!is_null($this->alsoCheck)){
              foreach($this->alsoCheck as $field => $val){
                if (!isset($user->$field) || $user->$field != $val){
                  $return['message'] = \df_string('errors:invalidlogin');
                  return $return;
                }
              }
            }

            // At this point, everything should be ok, so set the user in a session
            if (!($session = $this->addSession($uID))){
                $return['message'] = \df_string('errors:syserror');
                return $return;
            }

            $return['session'] = $session;
            $return['result'] = true;

        } else {
            $return['message'] = \df_string('errors:invaliduserconfig');
        }

        return $return;

    }

    public function logout(){
        return \DF\Helpers\Session::destroy();
    }

    /**
     * Check if the user is logged in (if the session key for this site is set)
     * @return type
     */
    public function isLoggedIn(){
        return (\DF\Helpers\Session::read( self::getSessionKey() ) !== false);
    }


    /**
     * Write the session data
     * @global \DF\Helpers\type $cfg
     * @param type $uID
     * @return type
     */
    protected function addSession($uID){

        $key = self::getSessionKey();

        // Write to the actual session
        return \DF\Helpers\Session::write($key, $uID);

    }

    /**
     * Get a user record from its id
     * @global \DF\Helpers\type $cfg
     * @param type $id
     * @return boolean
     */
    protected function getUser($id){

        $user = $this->db->select($this->table, array('id' => $id));
        if (!$user){
            return false;
        }

        return $user;

    }

    /**
     * Get the currently authenticated user
     * @return type
     */
    public function getLoggedInUserID(){
        return \DF\Helpers\Session::read( self::getSessionKey() );
    }

    /**
     * Get the id of a user record from its ident
     * @global type $cfg
     * @param type $ident
     * @return type
     */
    protected function getUID($ident){

        $user = $this->db->select($this->table, array($this->ident_field => $ident), null, 'id');
        return ($user) ? $user->id : false;

    }

    /**
     * Hashes a specified password (setPassword)
     * Returns a hashed string on success, or FALSE on error
     * @return mixed
     * @throws \DF\DFException
     */
    public function hash(){

        if (!$this->password){
            return false;
        }

        return password_hash($this->password, $this->method);

    }

    /**
     * Hash a password using the specified password, salt and method and then compare it to a given hash (most likely from your users database table)
     * @param type $dbHash
     * @return boolean
     */
    public function compare($stored){
        return (password_verify($this->password, $stored));
    }


    /**
     * Reset the properties on the object
     */
    public function reset(){
        $this->password = null;
        $this->method = self::DEFAULT_METHOD;
    }


    /**
     * Get the key used for the sessions on this site
     * @global \DF\Helpers\type $cfg
     * @return type
     */
    public function getSessionKey(){
        return $this->session_key;
    }



}
