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
 * Database
 * 
 * This interface sets out all the methods required by any Database implementations
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/
namespace DF;

interface Database {

    public function __construct($driver, $host, $user, $pass);
    public function get();
    public function connect($db = false);
    public function disconnect();
    
    // Queries (RW)
    public function execute($sql); # Execute pure SQL without any PDO. Returns a query resource
    public function query($sql, $params = array()); # Returns query resource
    public function select($table, $params = array(), $order = null, $fields = '*'); # Returns first row of Recordset object or false
    public function selectAll($table, $params = array(), $order = null, $fields = '*', $limitFrom = 0, $limit = null); # Returns Recordset object or false
    public function selectSQL($sql, $params = array()); # Returns Recordset object or false
    public function selectRecord($table, $where = null, $params = array(), $fields="*", $order = null); # Returns first row of recordset or false
    public function selectRecords($table, $where = null, $params = array(), $fields="*", $order = null); # Returns Recordset object or false
    public function update($table, array $params, array $where = null, $limit = 1); # Return number of affected records
    public function insert($table, array $params); # Return id()
    public function delete($table, $params = array(), $limit = null); # Return number of affected records
    public function count($table, $params = array(), $field = "*"); # Returns int
    public function countSQL($sql, $params = array()); # Returns int
    public function numRows(); # Returns int
    public function id(); # Returns last insert id
           
    // Set/Get system bits and bobs
    public function setAttribute($att, $val); # Set any attribute
    public function setFetchMethod($method); # Set the fetch mode attribute E.g. OBJ, ASSOC, etc...
    public function setPrefix($prefix); # Incase you want to prefix all table names
    public function getMessage(); # Return error message
    
    // Affect values
    public function escape($val); # Escapes a value if you are being a bellend and running a direct query without using PDO
    public function wrap($val); # Wrap a field/table name with database-specific syntax. E.g. MySQL: `field`, MS SQL: [field], Oracle: "field", etc... in case of fields with spaces (rare but possible)
    public function preLimit($limit); # If the db engine has a limit before the select fields, e.g. select top 1, select first 1, apply that
    public function postLimit($limit, $limitFrom = null); # If the db engine has a limit after the rest of the query, e.g. limit 1, apply that
    
    // Transactions
    public function start(); # Start transaction
    public function end(); # End transaction
    public function rollback(); # Rollback transaction
    public function commit(); # Commit transaction
    
    // Meta Data
    public function tableExists($table); # Returns bool
    public function listTables(); # Returns Recordset of table info
    public function fieldExists($table, $field); # Returns bool
    public function listFields($table); # Returns Recordset of field info
    public function printTable($table); # Print out the information about a table and its fields
    
    // Caching
    public function cache($bool); # Turn caching on/off (t/f)
    public function cacheDelete(array $path); # Delete a cache file in the path specified, e.g. /cache/queries/reports/view/7
    public function cacheClear(); # Delete all cached queries
    
    // Misc
    public function call($function, $params = null); # This calls any driver-specific functions not included in the DB layer. E.g. $DB->call('query'); would call mysql_query (if using MySQL)
    public function dump(); # Dumps the current object's state
    

}




/**
 * RecordSet class
 * 
 * Results from DB queries will be put into a RecordSet, which can then be traversed
 */
class Recordset {
    
    private $data;
    private $tmp;
    private $rowNum = 0;
    
    /**
     * Construct object and load in data (array or object probably)
     * @param type $data 
     */
    public function __construct($data){
        // if not an array for some reason, make it one
        if (!is_array($data)) $data = array($data);
        $this->data = $data;
        $this->tmp = $data;
    }
    
    /**
     * Return all rows
     */
    public function all(){
        return $this->data;
    }
    
    /**
     * Get the next row in the recordset
     */
    public function row(){
        
        // If there is an element, return it and increment which row we are on
        if (isset($this->data[$this->rowNum])){
            $this->rowNum++;
            return $this->data[$this->rowNum - 1];
        } else {
            return false;
        }
        
    }
    
    /**
     * Count the records in this set
     */
    public function count(){
        return count($this->data);
    }
    
    /**
     * Return how many records are left that we haven't accessed yet
     */
    public function left(){
        return ($this->count() - $this->rowNum);
    }
    
}

include_once df_SYS_CORE . 'DB' . df_DS . 'PDO.php';