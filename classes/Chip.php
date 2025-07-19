<?php

$structure=[
    "command"=>"varchar(100)",
    "params"=>"varchar(400)",
    "target" => "int",
    "submitter" => "int",
    "time"=>"int"
];

Module::DemandTable("chip_commands",$structure, true);
/**
 * Description of Chip
 *
 * @author admin
 */
class Chip
{
    public static function SendCommand($gname, $command, $params, $time = 0)
    {
        $group = UserGroup::FromName($gname);
        if(!$group)
        {
            return false;
        }
        foreach($group->GetMembers() as $uid)
        {
            DBHelper::Insert("chip_commands", [null, $command, $params, $uid, EngineCore::$CurrentUser->userid, $time]);
        }
        
    }
    
    public static function GetCommands($uid)
    {
        $q = DBHelper::Select("chip_commands",['id','command','params'], ["target"=>$uid]);
        $q.= "AND time < ".time();
        $commands = DBHelper::RunTable($q,[$uid]);
        foreach($commands as $command)
        {
            DBHelper::Delete("chip_commands",['id'=>$command['id']]);
        }
        return  $commands;
    }
}
