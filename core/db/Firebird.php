<?php
/**
 * 
 * THIS CLASS IS NOT FINISHED, SOME THINGS DON'T WORK, E.G. LASTINSERTID, CAN'T FIND A WAY TO GET THAT, EVEN WITH RETURNING "ID"
 * 
 * CHOOSE BETWEEN THIS AND SQLITE AS A PACKAGED DB ENGINE
 * 
 * 
 */


/**
 * Description of Firebird
 * 
 * @copyright 28-Apr-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 */

namespace DF\DB;

class Firebird extends \DF\DB\PDO {

    
    /**
     * This calls any driver-specific functions not included in the DB layer. E.g. $DB->call('db_info'); would call ibase_db_info()
     * @param string $function 
     */
    public function call($function, $params = null) {
        if (function_exists('ibase_'.$function)){
            return call_user_func('ibase_'.$function, $params);
        } else {
            return false;
        }
    }

    public function fieldExists($table, $field) {
                
        $st = $this->query('select rdb$field_name from rdb$relation_fields where rdb$relation_name = ? and rdb$field_name = ?', array( strtoupper($table), strtoupper($field) ));
        $cnt = ($st) ? count( $st->fetchAll() ) : 0;
        return ($cnt == 0) ? false : true;
        
    }

    /**
     * List all the fields in a given table
     * @param string $table Table name
     */
    public function listFields($table){
        
        $st = $this->query('select rdb$field_name from rdb$relation_fields where rdb$relation_name = ?', array( strtoupper($table) ));
        if ($st)
        {
            $st->setFetchMode(\PDO::FETCH_OBJ);
            $names = array();
            foreach($st->fetchAll() as $row){
                $names[] = $row;
            }
            $data = new \DF\Recordset($names);
            return $data;
        }
        else
        {
            return false;
        }
        
    }

     /**
     * List all tables in the database
     */
    public function listTables(){
        
        $st = $this->query('SELECT a.RDB$RELATION_NAME
                            FROM RDB$RELATIONS a
                            WHERE RDB$SYSTEM_FLAG = 0 AND RDB$RELATION_TYPE = 0');
        $names = array();
        foreach($st->fetchAll() as $row){
            $names[] = current($row);
        }
        $data = new \DF\Recordset($names);
        return $data;
        
    }

    
    public function printTable($table) {
        
        echo "not yet supported";
        
    }

    public function tableExists($table) {
        
        $st = $this->query('SELECT a.RDB$RELATION_NAME
                            FROM RDB$RELATIONS a
                            WHERE RDB$SYSTEM_FLAG = 0 AND RDB$RELATION_TYPE = 0 AND a.RDB$RELATION_NAME = ?', array( strtoupper($table) ));
        
        $cnt = count( $st->fetchAll() );
        
        return ($cnt == 0) ? false : true;
        
        
        
    }

    /**
     * Wraps a table/field in Firebird-specific syntax
     * It will be wrapped in "double quotes" which means it becomes case sensitive, so it is converted to uppercase
     * @param type $val 
     */
    public function wrap($val){
        
        // If the user has wrapped it themself, don't do anything
        if (strpos($val, '"') !== false) return $val;
        
        // If the field contains a fullstop, then it is referencing another table/view, so we don't want the wrap the whole thing
        if (strpos($val, ".") !== false){
            $exp = explode(".", $val);
            $tmp = array();
            foreach($exp as $e){
                $tmp[] = $this->wrap($e);
            }
            $val = implode(".", $tmp);
            return $val;
        } else {        
            return '"'.strtoupper($val).'"';
        }
        
    }

    public function postLimit($limit) {
        if (!is_null($limit)){
            return " ROWS {$limit} ";
        }
    }

    public function preLimit($limit) {}

    public function id() {
        return false;
    }
    
    public function returning($field) {
        return " RETURNING {$this->wrap($field)} ";
    }

}