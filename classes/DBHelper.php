<?php

//if(!isset($_GET['showerror']))
error_reporting(0);

error_reporting(E_ALL);

//phpinfo();
//mysql_connect(DB_SERVER,DB_USER,DB_PASS);
//mysql_select_db(MAIN_DATABASE);

class DBHelper
{

    public static PDO $DBLink;

    /*
      public static function Q($query)
      {
      $res=mysql_query($query);
      $err=mysql_error();
      if($err!="")
      {
      Utility::debug($query);
      Utility::debug( $err );
      }
      }
      // */
    // FIXED-ISH

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
     * Creates the string containing placeholders chained by AND from an array of value assignments
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
     * Runs a prepared statement and discards the result
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
    
    /**
     * Runs a prepared statement and returns the object for further inquiry (affected rows etc)
     * @param string $query The query to prepare and run
     * @param array $params Array of params to bind
     * @return PDOStatement
     */
    public static function RunStmt($query, $params): PDOStatement
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
        return $stmt;
    }
    
    
    /**
     * Run a query and get the results
     * @param string $query The query to prepare and run
     * @param array $params Array of params to bind
     * @return array An array of rows (each an assoc array of column-value)
     */
    public static function RunTable($query, $params)
    {
        $stmt = self::RunStmt($query,$params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function RunRow($query,$params)
    {
        
        $stmt = self::RunStmt($query,$params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public static function RunList($query,$params,$column=0)
    {
        $stmt = self::RunStmt($query,$params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, $column);
    }
    
    public static function RunScalar($query,$params,$column=0)
    {
        $stmt = self::RunStmt($query,$params);
        return $stmt->fetch(PDO::FETCH_COLUMN, $column);
    }
    
    
    /**
     * Deletes from a table. 
     * @param string $table The table to delete from
     * @param array $where An associative array of WHERE conditions ['column'] => ['value']
     * @todo make this return an int indicating number of rows deleted / -1 on fail
     */
    public static function Delete($table, $where)
    {
        // get the param values
        $params = array_values($where);
        DBHelper::RunStmt("DELETE FROM $table WHERE " . self::Where($where), $params);
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

    public static function Count($table, $column, $where)
    {
        $query = "SELECT COUNT($column) FROM $table WHERE " . self::Where($where);
        $params = array_values($where);
        return self::RunScalar($query,$params,0);
    }
    
    //DEPRECATED
    public static function GetArray($stmt)
    {
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();
        $data = [];
        foreach($stmt as $row)
        {
            $data[] = $row;
        }
        $err = $stmt->errorCode();
        if($err !== "   ")
        {
            //    Utility::debug($query);
            Utility::debug($err);
        }
        return $data;
    }

    //DEPRECATED
    public static function GetList($query)
    {
        $data = Array();
        $results = DBHelper::GetArray($query);
        $c = count($results);
        for($i = 0; $i < $c; $i++)
        {
            $data[] = array_values($results[$i])[0];
        }
        return $data;
    }

    //DEPRECATED
    public static function GetCount($table, $column, $where = "")
    {
        $whereclause = "";
        if($where !== "")
        {
            $whereclause = "WHERE " . $where;
        }
        $stmt = DBHelper::$DBLink->prepare("SELECT COUNT(?) FROM ? $whereclause");
        $stmt->bindParam(1, $column);
        $stmt->bindParam(2, $table);
        $count = DBHelper::GetList($stmt)[0];
        return $count;
    }

    //DEPRECATED
    public static function GetOneRow($query)
    {
        $data = DBHelper::GetArray($query);
        return $data[0];
    }

    /**
     * Inserts values into the table
     * @param type $table Name of the table
     * @param type $values Array of values to insert
     * @return type TODO something to indicate success or fail
     */
    public static function Insert($table, $values)
    {
        //for each value to insert
        //$values=DBHelper::BracketRoll($values);
        if(count($values) < 1)
        {
            return;
        }
        $valuesstring = "(";
        $valuesstring .= str_repeat("?,", count($values) - 1);
        $valuesstring .= "?)";
        self::RunStmt("INSERT into $table VALUES $valuesstring", $values);
    }

    //FIXED
    public static function ValueExists($table, $column, $value)
    {
        $count = self::Count($table, $column, [$column => $value]);
        return ($count > 0);
    }

    //FIXED
    public static function GetLastId()
    {
        return DBHelper::$DBLink->lastInsertId();
    }
}

DBHelper::$DBLink = new PDO('mysql:host=' . DB_SERVER . ';dbname=' . MAIN_DATABASE, DB_USER, DB_PASS);

/*
 * /**
         * @todo move the error checking code somewhere more general
         *
        
        $err = $stmt->errorCode();
        if($err !== "   ")
        {
            Utility::debug($err);
        }
        
        //////////////////////////////////////////////
 */
