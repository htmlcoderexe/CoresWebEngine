<?php
define("MODULE_DIR","modules/");
class Module
{
    public $name;
    public $funcprefix="ModuleAction";
    private $immafaultyshit=false;
    
    public const SPLIT_ROUTE = 7;
    
    public static function DemandProperty($propname,$dname="",$desc="")
    {
        if(!EVA::GetPropertyId($propname))
        {
            EVA::CreateProperty($propname,$dname,$desc);
        }
    }
    
    public static function DemandTable($table,$structure,$useIdColumn=true,$cacheSuccess=true)
    {
        $result=DBHelper::VerifyTable($table, $structure, $useIdColumn, $cacheSuccess);
        if($result==DBHelper::VERIFICATION_TABLE_OK)
        {
            return;
        }
        if($result==DBHelper::VERIFICATION_TABLE_MISSING)
        {
            DBHelper::MakeTable($table,$structure,$useIdColumn);
            return;
        }
        EngineCore::Write2Debug("Failure. Table is not correct.");
    }
    
    public static function SplitRoute()
    {
        
    }
    function __construct($name)
    {
        $filename=MODULE_DIR.basename($name)."/main.php";
        if(!file_exists($filename))
        {
            EngineCore::Write2Debug("<strong>Module failed to load:</strong> Module <em>'$name'</em> was not found.");
            $this->immafaultyshit=true;
            return;
        }
        $this->name=$name;
        require_once $filename;
    }
    
    function PerformAction($actionstring,$params)
    {
        if($this->immafaultyshit)
        {
            return;
        }
        $action=basename($actionstring);
        $funcname=$this->funcprefix."_".$this->name."_".$action;
        if(!function_exists($funcname))
        {
                EngineCore::Write2Debug("<strong>Failed to perform action:</strong> Action <em>$action</em> not found in module <em>{$this->name}</em>");
                $this->immafaultyshit=true;
                return;
        }
        else
        {
            $result = call_user_func($funcname,$params);
            
            if($result == self::SPLIT_ROUTE)
            {
                $next_level = array_shift($params);
                $this->PerformAction($action . "_" . $next_level, $params);
            }
        }
    }
}
$struct=[
    "name"=>"varchar(255)",
    "time"=>"int"
];
Module::DemandTable("verified_tables",$struct,true,false);