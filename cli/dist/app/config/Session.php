<?php
/**
 * This file should take the value from the session and load it into a user object called $User
 * This variable $User should only ever be used by the session and not used as a variable name for anything else
 * This $User variable can be an array or an object, or whatever you want, it will simply be checked to see if it isset when controllers require authentication to access
 * 
 * Example usage:
 * 
 * $User = $db->select('myusertable', array('id' => \DF\Helpers\Session::read( \DF\Helpers\Auth::getSessionKey() )));
 * 
 * The \DF\Helpers\Auth::getSessionKey() method gets the name of the key used for the session in this application
 * The \DF\Helpers\Session::read() method reads the data from the $_SESSION array with that key
 * 
 * Or you could call a custom User object and load it that way.
 * Or do whatever you want really.
 * 
 */