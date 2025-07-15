<?php

//if(!isset($_GET['showerror']))
error_reporting(0);

error_reporting(E_ALL);

//phpinfo();
//mysql_connect(DB_SERVER,DB_USER,DB_PASS);
//mysql_select_db(MAIN_DATABASE);
define("DB_VERIFICATION_TABLE","verified_tables");

class DBHelper
{
    public const VERIFICATION_TABLE_OK=0;
    public const VERIFICATION_TABLE_EXISTS=1;
    public const VERIFICATION_TABLE_MISSING=2;
    public static PDO $DBLink;
    public static $DEBUG=false;


    //=========BEGIN Helper functions==============
    //
    //      These functions help with formatting
    //      function params into proper SQL.
    //
    
    
    /**
     * Flattens out an assoc array like ['key'] => ['value'] into 'key', 'value'
     * @param type $dictionary
     * @return type
     */
    public static function Flatten($dictionary)
    {
        $flat = [];
        foreach($dictionary as $field => $value)
        {
            $flat[] = $field;
            $flat[] = $value;
        }
        return $flat;
    }

    /**
     * Creates the string containing placeholders chained by ',' from an array of value assignments
     * @param array $set An associative array of value assignments ['column'] => ['value']
     * @return string The complete assignment string
     */
    public static function Set($set)
    {
        $assignment_list = "";
        $multiple = false;
        foreach($set as $field => $value)
        {
            if($multiple)
            {
                $assignment_list .= ", ";
            }
            $assignment_list .= "$field = ?";
            $multiple = true;
        }
        return $assignment_list;
    }

    /**
     * Creates the string containing placeholders chained by AND from an array of conditions
     * @param array $where An associative array of WHERE conditions ['field'] => ['value']
     * @return string The complete condition string
     */
    public static function Where($where)
    {
        $whereclause = "";
        $multiple = false;
        foreach($where as $field => $value)
        {
            if($multiple)
            {
                $whereclause .= " AND ";
            }
            $whereclause .= "$field = ?";
            $multiple = true;
        }
        return $whereclause;
    }
    
    /**
     * Formats a SELECT query.
     * @param string $table Table to SELECT from.
     * @param string[] $fields Fields to SELECT.
     * @param array $where An associative array of WHERE clauses of the form
     * ['field'=>'value'].
     * @param array $orderby Fields to ORDER BY, as an associative array of ['field'=>'mode']
     * with mode being either ASC or DESC 
     * @param int $limit If not 0, limit the results to this number.
     * @param int $offset If not 0, start the results at this number.
     * @return string A fully formatted SELECT query with placeholders.
     */
    public static function Select($table,$fields,$where,$orderby=[],$limit=0,$offset=0)
    {
        $q="SELECT ". implode(",",$fields) . " FROM $table ".(count($where)>0?"WHERE ":"").self::Where($where);
        if(count($orderby)>0)
        {
            $q.=" ORDER BY ";
            $multiple=false;
            foreach($orderby as $field=>$mode)
            {
                if($multiple)
                {
                    $q.=", ";
                }
                $q.="$field $mode";
                $multiple=true;
            }
        }
        if($limit>0)
        {
            $q.=" LIMIT $offset,$limit";
        }
        return $q;
    }
    
    //=========END Helper functions================
    
    
    
    //=========BEGIN Generic query execution=======
    //
    //      These functions all perform take a query
    //      and an array of params to bind and 
    //      return various types of objects for
    //      convenience of use.
    //

    
    /**
     * Prepares and runs a query and returns the statement object for further use.
     * @param string $query The query to prepare and run
     * @param array $params Array of params to bind
     * @return PDOStatement The statement object which can then be used for fetching results or repeated queries.
     */
    public static function RunStmt($query, $params): PDOStatement
    {
        if(self::$DEBUG)
        {
            EngineCore::Write2Debug("<strong>Executing MySQL query:</strong>");
            EngineCore::Write2Debug($query);
            EngineCore::Write2Debug("<em>[".implode("|",$params)."]</em>");EngineCore::Write2Debug("<strong>Executing MySQL query:</strong>");
            EngineCore::Write2Debug($query);
            EngineCore::Write2Debug("<em>[".implode("|",$params)."]</em>");
        }
        
        // prep the statement
        $stmt = DBHelper::$DBLink->prepare($query);
        // bind all params
        for($i = 0; $i < count($params); $i++)
        {
            $stmt->bindParam($i + 1, $params[$i]);
        }
        // run the thing
        $stmt->execute();
        return $stmt;
    }
    
    /**
     * Runs a query and gets the results
     * @param string $query The query to prepare and run
     * @param array $params Array of params to bind
     * @return array An array of rows (each an assoc array of column=>value)
     */
    public static function RunTable($query, $params)
    {
        $stmt = self::RunStmt($query,$params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Runs a query and gets a single row
     * @param string $query The query to prepare and run
     * @param array $params Array of params to bind
     * @return array An assoc array of column=>value
     */
    public static function RunRow($query,$params)
    {
        
        $stmt = self::RunStmt($query,$params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Runs a query and gets a single column as a list
     * @param string $query The query to prepare and run
     * @param array $params Array of params to bind
     * @param int the column to return
     * @return array An array of values from the selected column
     */
    public static function RunList($query,$params,$column=0)
    {
        $stmt = self::RunStmt($query,$params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, $column);
    }
    
    /**
     * Runs a query and gets a single value
     * @param string $query The query to prepare and run
     * @param array $params Array of params to bind
     * @param int the column to return
     * @return mixed a value from the selected column in the returned row
     */
    public static function RunScalar($query,$params,$column=0)
    {
        $stmt = self::RunStmt($query,$params);
        return $stmt->fetch(PDO::FETCH_COLUMN, $column);
    }
    
    /**
     * Runs a query and discards the result
     * @param string $query The query to prepare and run
     * @param array $params Array of params to bind
     */
    public static function RunVoid($query, $params)
    {
        // prep the statement
        $stmt = DBHelper::$DBLink->prepare($query);
        // bind all params
        for($i = 0; $i < count($params); $i++)
        {
            $stmt->bindParam($i + 1, $params[$i]);
        }
        // run the thing
        $stmt->execute();
    }
    
    //=========END Generic query execution=========
    
    
    
    //=========BEGIN Specific queries==============
    //
    //      These functions perform specific tasks
    //      and return relevant information
    //
    
    
    /**
     * Inserts values into the table
     * @param type $table Name of the table
     * @param type $values Array of values to insert
     * @return type TODO something to indicate success or fail
     */
    public static function Insert($table, $values)
    {
        if(count($values) < 1)
        {
            return;
        }
        $valuesstring = "(";
        $valuesstring .= str_repeat("?,", count($values) - 1);
        $valuesstring .= "?)";
        self::RunStmt("INSERT into $table VALUES $valuesstring", $values);
    }

    /**
     * Updates data in a table
     * @param type $table The table to UPDATE
     * @param type $assignments Associative array of changes ['column'] => ['new value']
     * @param type $where An associative array of WHERE conditions ['column'] => ['value']
     * @todo make this return an int number of affected rows / -1 on fail
     */
    public static function Update($table, $assignments, $where)
    {
        $params = array_merge(array_values($assignments), array_values($where));
        DBHelper::RunStmt("UPDATE $table SET " . self::Set($assignments) . " WHERE " . self::Where($where), $params);
    }
    
    /**
     * Deletes from a table. 
     * @param string $table The table to delete from.
     * @param array $where An associative array of WHERE conditions ['column'] => ['value']
     * @todo make this return an int indicating number of rows deleted / -1 on fail
     */
    public static function Delete($table, $where)
    {
        $params = array_values($where);
        DBHelper::RunStmt("DELETE FROM $table WHERE " . self::Where($where), $params);
    }
    
    /**
     * SELECTS then COUNTS, returning the number of rows returned.
     * @param string $table Table to search.
     * @param string $column Column to count.
     * @param array $where An associative array of WHERE conditions ['column'] => ['value']
     * @return int The number of rows matching the WHERE conditions.
     */
    public static function Count($table, $column, $where)
    {
        $query = "SELECT COUNT($column) FROM $table WHERE " . self::Where($where);
        $params = array_values($where);
        return self::RunScalar($query,$params,0);
    }

    /**
     * Returns the last inserted ID.
     * @return int The last inserted ID.
     */
    public static function GetLastId()
    {
        return DBHelper::$DBLink->lastInsertId();
    }
    
    //=========END Specific queries================
    
    
    
    //=========BEGIN System functions==============
    //
    //      These functions are mostly used
    //      to manage tables and other
    //      system-wide actions best restricted
    //      from regular users.

    
    /**
     * Checks if a table matching the specifications exists, also caches the result.
     * The first three params are identical to DBHelper::MakeTable().
     * @param string $name Name of the table.
     * @param array $fields Associative array of ['fieldname'=>'fieldtype'], with standard DB types.
     * @param bool $useID If true, includes an 'id' column used as primary key.
     * @param bool $useCache If false, does not check for cached results and does not cache this result.
     * @return int Status of the verification as defined in constants.
     */
    public static function VerifyTable($name,$fields,$useID,$useCache)
    {
        if($useCache)
        {
            $verify=self::RunScalar("SELECT time FROM ".DB_VERIFICATION_TABLE." WHERE name = ?",[$name]);
            if($verify)
            {
                return self::VERIFICATION_TABLE_OK;
            }
        }
        //$exists=self::RunTable("SHOW TABLES LIKE $name",[]);
        /*
        if(!$exists)
        {
             return self::VERIFICATION_TABLE_MISSING;
        }
        //*/
        
        try
        {
            $table=self::RunTable("SHOW COLUMNS FROM $name",[]);
        } catch (Exception $ex) {
            return self::VERIFICATION_TABLE_MISSING;
        }
        
        
        if(!$table)
        {
            return self::VERIFICATION_TABLE_MISSING;
        }
        foreach($table as $row)
        {
            if(isset($fields[$row['Field']]))
            {
                if($fields[$row['Field']]==$row['Type'])
                {
                    
                }
                else
                {
                    return self::VERIFICATION_TABLE_EXISTS;
                }
            }
            else
            {
                if($useID && $row['Field']=='id')
                {
                    if($row['Key']=="PRI" && $row['Extra']=="auto_increment")
                    {
                        
                    }
                    else
                    {
                        return self::VERIFICATION_TABLE_EXISTS;
                    }
                }
                else
                {
                     return self::VERIFICATION_TABLE_EXISTS;
                }
            }
        }
        if($useCache)
        {
            self::Insert(DB_VERIFICATION_TABLE,[NULL,$name,time()]);
        }
        return self::VERIFICATION_TABLE_OK;
        
    }
    
    /**
     * Creates a table using specified schema.
     * @param string $name Table name.
     * @param array $fields Associative array of ['fieldname'=>'fieldtype'], with standard DB types.
     * @param bool $useID If true, includes an 'id' column used as primary key.
     */
    public static function MakeTable($name,$fields,$useID)
    {
        $query="CREATE TABLE $name (";
        $first=true;
        if($useID)
        {
            $query.="id int NOT NULL AUTO_INCREMENT";
            $first=false;
        }
        foreach($fields as $fname=>$ftype)
        {
            $query.=$first?"":", ";
            $query.=$fname." ".$ftype;
            $first=false;
        }
        
        if($useID)
        {
            $query.=", PRIMARY KEY (id)";
        }
        $query.=")";
        self::RunStmt($query, []);
    }
    
    //=========END System functions================

    
}

DBHelper::$DBLink = new PDO('mysql:host=' . DB_SERVER . ';dbname=' . MAIN_DATABASE, DB_USER, DB_PASS);
