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
 * Session
 * 
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
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/
