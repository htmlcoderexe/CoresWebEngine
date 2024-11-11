<?php
define("MODULE_DIR","modules/");
class Module
{
    public $name;
    public $funcprefix="ModuleAction";
    private $immafaultyshit=false;
    
    public static function DemandProperty($propname,$dname="",$desc="")
    {
        if(!EVA::GetPropertyId($propname))
        {
            EVA::CreateProperty($propname,$dname,$desc);
        }
    }
    
    public static function DemandTable($table,$structure,$useIdColumn=true)
    {
        
    }
    
    function __construct($name)
    {
        $filename=MODULE_DIR.basename($name)."/main.php";
        if(!file_exists($filename))
        {
            Utility::debug("<strong>Module failed to load:</strong> Module <em>'$name'</em> was not found.");
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
                Utility::debug("<strong>Failed to perform action:</strong> Action <em>$action</em> not found in module <em>{$this->name}</em>");
                $this->immafaultyshit=true;
                return;
        }
        else
        {
                call_user_func($funcname,$params);
        }
    }
}
