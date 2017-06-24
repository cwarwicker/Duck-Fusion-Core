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

class MySQL extends \DF\DB\PDO {

    
    
    /**
     * Wraps a table/field in MySQL-specific syntax
     * @param type $val 
     */
    public function wrap($val){
        
        // If the user has wrapped it themself, don't do anything
        if (strpos($val, "`") !== false) return $val;
        
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
            return "`{$val}`";
        }
    } 
    
    
    /**
     * This calls any driver-specific functions not included in the DB layer. E.g. $DB->call('query'); would call mysql_query (if using MySQL)
     * @param string $function 
     */
    public function call($function, $params = null){
        if (function_exists('mysqli_'.$function)){
            return call_user_func('mysqli_'.$function, $params);
        } elseif (function_exists('mysql_'.$function)){
            return call_user_func('mysql_'.$function, $params);
        } else {
            return false;
        }
    } 
    
    
    /**
     * Check if a given table exists
     * @param type $table
     * @return type 
     */
    public function tableExists($table){
        
        $st = $this->query("SHOW TABLES LIKE ?", array($table));
        
        $cnt = count( $st->fetchAll() );
        
        return ($cnt == 0) ? false : true;
        
    }
    
    /**
     * List all tables in the database
     */
    public function listTables(){
        
        $st = $this->query("SHOW TABLES");
        $names = array();
        foreach($st->fetchAll() as $row){
            $names[] = current($row);
        }
        $data = new \DF\Recordset($names);
        return $data;
        
    }

    /**
     * Check if a field exists within a table
     * @param string $table Table name
     * @param string $field  Field name
     */
    public function fieldExists($table, $field){
        
        $st = $this->query("SHOW COLUMNS FROM {$this->convertTableName($table)} LIKE ?", array($field));
        
        $cnt = ($st) ? count( $st->fetchAll() ) : 0;
        
        return ($cnt == 0) ? false : true;
        
    }
    
    /**
     * List all the fields in a given table
     * @param string $table Table name
     */
    public function listFields($table){
        
        $st = $this->query("SHOW COLUMNS FROM {$this->convertTableName($table)}");
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
     * Count all the fields in a given table
     * @param string $table Table name
     */
    public function countFields($table){
        
        $st = $this->query("SHOW COLUMNS FROM {$this->convertTableName($table)}");
        $result = $st->setFetchMode(\PDO::FETCH_OBJ);
        return count( $st->fetchAll() );
        
    }
    
    /**
     * Print out the structural information of a table
     * @param type $table 
     */
    public function printTable($table){
        
        $sql = "SHOW FULL COLUMNS FROM {$this->convertTableName($table)}";

        $st = $this->query($sql);

        $num = 0;

        $disp = "<table class='struc'>";

        $disp .= "<tr class='rh'><th colspan='7'>".$table."</th></tr>";
        
        $disp .= "<tr class='rh'>";
            $disp .= "<th>Field</th>";
            $disp .= "<th>Type</th>";
            $disp .= "<th>Collation</th>";
            $disp .= "<th>Attributes</th>";
            $disp .= "<th>Null</th>";
            $disp .= "<th>Default</th>";
            $disp .= "<th>Extra</th>";
        $disp .= "</tr>";


        while($property = $st->fetch())
        {

            $class = (($num % 2) == 0) ? 'rE' : 'rO';
            $num++;
            
            if (strpos($property->Type, " ") !== false){
                $explodeType = explode(' ', $property->Type); 
                $type = $explodeType[0]; 
                unset($explodeType[0]);
                $att = "<span style='font-size:8pt;text-transform:uppercase;'>".implode(" ", $explodeType)."</span>";
            } else {
                $type = $property->Type;
                $att = '';
            }
            
            
            $extra = $property->Extra; 
            if($property->Key == 'PRI') $extra = 'primary_key, ' . $extra;

            $disp .= "<tr class='{$class}'>";
            $disp .= "<td>".$property->Field."</td>"; # Field Name
            $disp .= "<td>".$type."</td>"; # TYpe & Max Length
            $disp .= "<td>".$property->Collation."</td>"; # Collation
            $disp .= "<td>".$att."</td>"; # Attributes (binary, unsigned, unsigned zerofill, onupdate current timestamp)
            $disp .= "<td>".$property->Null."</td>"; # Null
            $disp .= "<td>".strtolower($property->Default)."</td>"; # Default
            $disp .= "<td>".$extra."</td>"; # Extra
            $disp .= "</tr>";

        }

        $disp .= '</table>';
        echo $disp;
        
    }

    public function postLimit($limit, $limitFrom = null) {
        if (!is_null($limit)){
            if (!is_null($limitFrom)){
                return " LIMIT {$limitFrom}, {$limit} ";
            } else {
                return " LIMIT {$limit} ";
            }
        }
    }

    public function preLimit($limit) {}


}
