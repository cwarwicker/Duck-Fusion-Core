<?php

/**
 * Description of MySQL
 * 
 * @copyright 28-Apr-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace DF\DB;

abstract class PDO implements \DF\Database {

    static $supported_dbs = array(
        "mysql" => "MySQL"
    );


    protected $driver;
    
    protected $DBC = false;
    private $host;
    private $user;
    private $pass;
    protected $dbname = false;
    protected $prefix = '';
    protected $lastSt = null;
    
    /**
     * Set connection details
     * @param type $host
     * @param type $user
     * @param type $pass 
     */
    public function __construct($driver, $host, $user, $pass) {
        $this->driver = $driver;
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
    }
    
    public function __destruct() {
        $this->disconnect();
    }
    
    /**
     * Get the connection
     * @return type 
     */
    public function get(){
        return $this->DBC;
    }
    
    protected function clearConnectionDetails(){
        $this->host = false;
        $this->user = false;
        $this->pass = false;
        $this->dbname = false;
    }
    
    /**
     * Connect to database server
     * @param string $db Database name to connect to
     */
    public function connect($db = false){
        
        global $cfg;
        
        try {
            
            // If we haven't specified a database name
            if (!$db){
                $conn = new \PDO("{$this->driver}:host={$this->host};charset={$cfg->db_charset}", $this->user, $this->pass);
            }
            else {
                $this->dbname = $db;
                $conn = new \PDO("{$this->driver}:host={$this->host};dbname={$db};charset={$cfg->db_charset}", $this->user, $this->pass);
            }
            
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
            $this->DBC = $conn;
                        
        } catch (\PDOException $e){
            $this->clearConnectionDetails();
            throw $e;
        }
        
        // Clear connection details - don't need them any more
        $this->clearConnectionDetails();
        
        return true;
                
    }
    
    /**
     * Disconnect from DB
     */
    public function disconnect(){
        $this->DBC = null;
    }
    
    // Queries (RW)
    
    /**
     * Execute plain SQL
     * @param string $sql 
     */
    public function execute($sql){
        
        if (!$this->DBC) return false;
        
        try {
            return $this->DBC->query($sql);
        } catch(\PDOException $e){
            echo $e->getMessage();
        }
    } 
    
    /**
     * Execute a query using the normal prepared statement method
     * @param type $sql
     * @param array $params
     */
    public function query($sql, $params = array()){
    
        if (!$this->DBC) return false;
        
        try {
            $st = $this->DBC->prepare($sql);
            $st->execute($params);
            $this->lastSt = $st;
            return $st;
        } catch (\PDOException $e){
            echo $e->getMessage();
        }
        
    }
    
    /**
     * Convert a table name, so we don't have to specify the prefix in SQL, we can wrap the table in curly braces
     * @param type $table 
     */
    public function convertTableName($table){
        
        $table = $this->prefix . $table;
        return $this->wrap($table);
        
    }
    
    /**
     * Process a string of SQL and replace any {table_names} with `prefix_table_name`
     * @param string $sql
     * @return string The processed SQL
     */
    private function processSQLForTableNames($sql){
        
        // Find any curly brackets in the sql and convert them to table names using wrap & prefix
        $t = $this;
        return preg_replace_callback("/\{(.+?)\}/i", function($params) use ($t) {
            return $t->convertTableName($params[1]);
        }, $sql);
                        
    }
    
    /**
     * Selects ONE record from a table
     * @param string $table The name of the table
     * @param array $params An array of parameters to use in the WHERE clause, e.g. array("name" => "conn") or array("name" => array(":nm" => "conn"))
     * @param string $order AN ORDER BY clause (without the ORDER BY), e.g. "lastname ASC, firstname ASC"
     * @param string $fields A list of fields to select. Default is *
     * @return Recordset Recordset of results
     */
    public function select($table, $params = array(), $order = null, $fields = '*'){
        
        $results = $this->selectAll($table, $params, $order, $fields, 0, 1);
        if ($results)
        {
            return $results->row();
        }
        
        return false;
        
    }
    
    /**
     * Selects records from a table
     * @param string $table The name of the table
     * @param array $params An array of parameters to use in the WHERE clause, e.g. array("name" => "conn") or array("name" => array(":nm" => "conn"))
     * @param string $order AN ORDER BY clause (without the ORDER BY), e.g. "lastname ASC, firstname ASC"
     * @param string $fields A list of fields to select. Default is *
     * @param int $limitFrom Limit from this record 
     * @param int $limit Limit to this number of records
     * @return Recordset Recordset of results
     */
    public function selectAll($table, $params = array(), $order = null, $fields = '*', $limitFrom = 0, $limit = null){
        
        if (!$this->DBC) return false;
        
        try {
        
            if (!is_array($params)) $params = (array)$params;
            
            $sqlParams = array();
            $sql = "";
            $sql .= "SELECT {$fields} ";
            $sql .= "FROM {$this->convertTableName($table)} ";

            if (!empty($params)){
                $sql .= "WHERE ";
                foreach($params as $field => $val){

                    // If val itself is an array, then we are using named params
                    if (is_array($val)){
                        $key = key($val);
                        $sql .= "{$this->wrap($field)} = {$key} AND ";
                        $sqlParams[$key] = $val[$key];
                    }
                    // Otherwise we are using unnamed
                    else
                    {
                        $sql .= "{$this->wrap($field)} = ? AND ";
                        $sqlParams[] = $val;
                    }
                }
                $sql = substr($sql, 0, -4);
            }

            if (!is_null($order)){
                $sql .= "ORDER BY {$order} ";
            }

            // Apply LIMIT
            $sql .= $this->postLimit($limit, $limitFrom);
            
            $st = $this->DBC->prepare($sql);
            $st->execute($sqlParams);
            $this->lastSt = $st;

            $results = new \DF\Recordset( $st->fetchAll() );
            
            if (!$results->all()) return false;
            
            return $results;
        
        } catch (\PDOException $e){
            echo $e->getMessage();
            return false;
        }
        
    }
    
    
    /**
     * Select one record, using SQL where clause instead of array
     * @param type $table
     * @param type $where
     * @param array $params
     * @param type $fields
     * @param type $order 
     */
    public function selectRecord($table, $where = null, $params = array(), $fields="*", $order = null){
        
        $sql = "SELECT {$fields} FROM {$this->convertTableName($table)} ";
        if (!is_null($where)){
            $sql .= "WHERE {$where} ";
        }
        
        $result = $this->selectSQL($sql, $params, $order, 0, 1);
        if ($result){
            return $result->row();
        }
        
        return false;
        
    }
    
    /**
     * Select records, using SQL where clause instead of array
     * @param type $table
     * @param type $where
     * @param type $fields
     * @param type $order 
     */
    public function selectRecords($table, $where = null, $params = array(), $fields="*", $order = null){
        
        $sql = "SELECT {$fields} FROM {$this->convertTableName($table)} ";
        if (!is_null($where)){
            // Check WHERE clause for table names to convert
            $where = $this->processSQLForTableNames($where);
            $sql .= "WHERE {$where} ";
        }
        
        $result = $this->selectSQL($sql, $params, $order);
        if ($result){
            return $result;
        }
        
        return false;
        
    }
    
    
    /**
     * Run a select statement using inputted SQL
     * @param string $sql The SQL
     * @param array $params An array of parameters to use
     * @return Recordset Recordset of results
     */
    public function selectSQL($sql, $params = array()){
        
        if (!$this->DBC) return false;
        
        try {
            
            if (!is_array($params)) $params = (array)$params;
            
            $sql = $this->processSQLForTableNames($sql);
                        
            $st = $this->DBC->prepare($sql);
            $st->execute($params);
            $this->lastSt = $st;

            $results = new \DF\Recordset( $st->fetchAll() );
            if (!$results->all()) return false;
            return $results;
        
        } catch (\PDOException $e){
            echo $e->getMessage();
            return false;
        }
        
    }
    
    /**
     * Update records in the database
     * @param string $table Table name
     * @param array $params An array of values to update, e.g. "userPoints" => 10, "userScore" => 100
     * @param array $where An array of fields and values to use in the WHERE clause, e.g. "userID" => 1. If we just want to update everything we can leave this null
     * @param int $limit The number of records to limit the update to. By default this will be 1 to stop accidental updating of lots of records where the where clause is wrong, so must be set
     * to null if you want no limit to be applied
     * @return int Number of rows affected
     */
    public function update($table, array $params, array $where = null, $limit = 1){
        
        if (!$this->DBC) return false;
        
        try {
        
            if (is_object($params)) $params = (array)$params;
            if (is_object($where)) $where = (array)$where;
            
            if (!is_array($params)) return false;
            
            $sqlParams = array();

            $sql = "UPDATE {$this->convertTableName($table)} ";
            $sql .= "SET ";
            
            // Fields to update
            foreach($params as $field => $val){

                // Named params
                if (is_array($val)){
                    $key = key($val);
                    $sql .= "{$this->wrap($field)} = {$key} , ";
                    $sqlParams[$key] = $val[$key];
                }
                // Unnamed params
                else
                {
                    $sql .= "{$this->wrap($field)} = ? , ";
                    $sqlParams[] = $val;
                    
                }

            }
            
            $sql = substr($sql, 0, -2);
            
            // Where
            if (!empty($where)){
                
                $sql .= "WHERE ";
                
                foreach($where as $field => $val){

                    // Named params
                    if (is_array($val)){
                        $key = key($val);
                        $sql .= "{$this->wrap($field)} = {$key} AND ";
                        $sqlParams[$key] = $val[$key];
                    }
                    // Unnamed params
                    else
                    {
                        $sql .= "{$this->wrap($field)} = ? AND ";
                        $sqlParams[] = $val;
                    }

                }
                
                $sql = substr($sql, 0, -4);
                
            }
 
            // Apply LIMIT
            $sql .= $this->postLimit("{$limit}");
            
            $st = $this->DBC->prepare($sql);
            $st->execute($sqlParams);
            $this->lastSt = $st;
                        
            return $st->rowCount();
        
        } catch (\PDOException $e){
            echo $e->getMessage();
            return false;
        }
        
    } 
    
    
    /**
     * Insert record(s) into the database
     * @param type $table
     * @param array $params 
     */
    public function insert($table, array $params){
        
        if (!$this->DBC) return false;
        
        try {
            
            if (is_object($params)) $params = (array)$params;
            if (!is_array($params)) return false;
            
            $sqlParams = array();
            $sql = "INSERT INTO {$this->convertTableName($table)} ";
            
            // Fields
            $sql .= "( ";
            
            foreach($params as $field => $val){
                $sql .= "{$this->wrap($field)} , ";
            }
            
            $sql = substr($sql, 0, -2);
            $sql .= ") ";
            
            
            // Vals
            $sql .= "VALUES ";
            $sql .= "( ";
            
            foreach($params as $field => $val){
                $sql .= "? , ";
                $sqlParams[] = $val;
            }
            
            $sql = substr($sql, 0, -2);
            $sql .= ") ";
                                    
            $st = $this->DBC->prepare($sql);
            $st->execute($sqlParams);
            $this->lastSt = $st;     
                        
            return $this->id();
            
        } catch (\PDOException $e){
            echo $e->getMessage();
            return false;
        }
        
    } 
    
    /**
     * Delete record(s) from a table
     * @param string $table The table name
     * @param array $params An array of params to use in the where clause to decide what to delete
     * @param int $limit The number of records to limit the DELETE to
     * @return int Number of affected rows
     */
    public function delete($table, $params = array(), $limit = null){
        
        if (!$this->DBC) return false;
        
        try {
         
            if (!is_array($params)) $params = (array)$params;
            
            $sql = "";
            $sqlParams = array();
            
            $sql .= "DELETE FROM {$this->convertTableName($table)} ";
            
            if (!empty($params)){
                
                $sql .= "WHERE ";
                
                foreach($params as $field => $val){
                    
                    // Named params
                    if (is_array($val)){
                        $key = key($val);
                        $sql .= "{$this->wrap($field)} = {$key} AND ";
                        $sqlParams[$key] = $val[$key];
                    }
                    // Unnamed params
                    else
                    {
                        $sql .= "{$this->wrap($field)} = ? AND ";
                        $sqlParams[] = $val;
                    }
                
                }
                
                $sql = substr($sql, 0, -4);
                
            }
            
            // Apply LIMIT
            $sql .= $this->postLimit("{$limit}");
                        
            $st = $this->DBC->prepare($sql);
            $st->execute($sqlParams);
            $this->lastSt = $st;
                        
            return $st->rowCount();
            
            
        } catch(\PDOException $e){
            echo $e->getMessage();
            return false;
        }
        
    }
    
    /**
     * Counts the number of records in a table
     * @param string $table Table name
     * @param array $params Field => Value for the WHERE clause
     * @param string $field By default it will do a COUNT(*), but if you want to speed up the count and only count one field, e.g. COUNT(id) , you can specify it
     */
    public function count($table, $params = array(), $field = "*"){
        
        if (!$this->DBC) return false;
        
        try {
            
            if (!is_array($params)) $params = (array)$params;
            $sql = "";
            $sqlParams = array();
            
            $sql .= "SELECT COUNT({$field}) as cnt FROM {$this->convertTableName($table)} ";
            
            if (!empty($params)){
                
                $sql .= "WHERE ";
                
                foreach($params as $field => $val){
                    
                    // Named params
                    if (is_array($val)){
                        $key = key($val);
                        $sql .= "{$this->wrap($field)} = {$key} AND ";
                        $sqlParams[$key] = $val[$key];
                    }
                    // Unnamed params
                    else
                    {
                        $sql .= "{$this->wrap($field)} = ? AND ";
                        $sqlParams[] = $val;
                    }
                
                }
                
                $sql = substr($sql, 0, -4);
                
            }
            
            $st = $this->DBC->prepare($sql);
            $st->execute($sqlParams);
            $this->lastSt = $st;

            $results = new \DF\Recordset( $st->fetchAll() );
            $row = $results->row();
            return (int)$row->cnt;            
            
        }
        catch(\PDOException $e){
            echo $e->getMessage();
            return false;
        }
        
    }
    
    
    /**
     * Count records using plain SQL for the SELECT and FROM elements, so that tables can be joined if you want
     * @param string $sql The SQL
     * @param array $params Params to bind
     * @return It will return the first field in your SELECT, so that first field must be the COUNT, otherwise it will return unexpected results
     */
    public function countSQL($sql, $params = array()){
        
        if (!$this->DBC) return false;
        
        $sql = $this->processSQLForTableNames($sql);
                
        $st = $this->DBC->prepare($sql);
        $st->execute($params);
        $this->lastSt = $st;

        $results = new \DF\Recordset( $st->fetchAll() );
        $row = (array) $results->row();
        
        return (int)current($row);   
        
    } 
    
    /**
     * Returns the number of rows affected by the last statement
     */
    public function numRows(){

        if (!$this->DBC) return false;
        if (!$this->lastSt) return false;
        
        return $this->lastSt->rowCount();
        
    }
    
    /**
     * Returns the ID of the last record inserted
     */
    public function id(){
        if (!$this->DBC) return false;
        return $this->DBC->lastInsertId();
    }
           
    // Set/Get system bits and bobs
    
    /**
     * Set fetch method (mode)
     * @param type $method 
     */
    public function setFetchMethod($method){
        $this->DBC->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, $method);
    } 
    
    /**
     * Set fetch method (mode)
     * @param type $method 
     */
    public function setAttribute($att, $val){
        $this->DBC->setAttribute($att, $val);
    } 
    
    /**
     * Set a table prefix
     * @param type $prefix 
     */
    public function setPrefix($prefix){
        $this->prefix = $prefix;
    } 
    
    public function getMessage(){} # Return error message
    
    // Affect values
    
    /**
     * Escapes a value if you are not using prepared statements for some reason
     * @param mixed $val 
     */
    public function escape($val){
        if (!$this->DBC) return false;
        return $this->DBC->quote($val);
    } 
    
    
    
    
    
    // Transactions
    
    /**
     * Begin a transaction
     * @return type 
     */
    public function start(){
        if (!$this->DBC) return false;
        return $this->DBC->beginTransaction();
    }
    
    /**
     * End a transaction - by committing
     */
    public function end(){
        return $this->commit();
    }
    
    /**
     * Rollbakc a transaction
     */
    public function rollback(){
        if (!$this->DBC) return false;
        return $this->DBC->rollback();
    }
    
    /**
     * Commit a transaction
     */
    public function commit(){
        if (!$this->DBC) return false;
        return $this->DBC->commit();
    }
        
    
    // Caching
    public function cache($bool){} # Turn caching on/off (t/f)
    public function cacheDelete(array $path){} # Delete a cache file in the path specified, e.g. /cache/queries/reports/view/7
    public function cacheClear(){} # Delete all cached queries
    
    // Misc
    /**
     * Dump out the current state of the DB object
     */
    public function dump(){
        var_dump($this);
    }
    
    public static function instantiate($driver, $host, $user, $pw){
        
        // If not supported, return false
        if (!array_key_exists($driver, self::$supported_dbs)){
            return false;
        }
        
        // Require file
        require_once self::$supported_dbs[$driver] . ".php";
        
        $classname = "\\DF\\DB\\" . self::$supported_dbs[$driver];
        $obj = new $classname($driver, $host, $user, $pw);
        return $obj;
        
    }

    
}
