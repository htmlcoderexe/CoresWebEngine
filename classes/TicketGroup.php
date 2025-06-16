<?php
Module::DemandProperty("owner","Owner","User owning this object.");
Module::DemandProperty("user_group","User group","User group linked to this object.");
/**
 * Description of TicketGroup
 *
 * @author admin
 */
class TicketGroup
{
    public $name;
    public $description;
    public $members;
    public $owner;
    public $id;
    public $func_group;
    public EVA $EVAobj;
    
    public function __construct($id)
    {
        $obj = new EVA($id);
        if($obj->id < 1)
        {
            return;
        }
        $this->name = $obj->attributes['name'];
        $this->description = $obj->attributes['description'];
        $this->EVAobj = $obj;
        $this->func_group = $obj->attributes['user_group'];
        $this->id = $id;
    }
    public function Update()
    {
        $this->EVAobj->SetSingleAttribute("name", $this->name);
        $this->EVAobj->SetSingleAttribute("user_group", $this->func_group);
        $this->EVAobj->SetSingleAttribute("description", $this->description);
        $this->EVAobj->Save();
    }
    
    public static function Create($name, $desc, $func_group)
    {
        $EVA = EVA::CreateObject("ticket_group");
        $EVA->SetSingleAttribute("name", $name);
        $EVA->SetSingleAttribute("user_group", $func_group);
        $EVA->SetSingleAttribute("description", $desc);
        $EVA->Save();
        return new TicketGroup($EVA->id);
    }
    
    public static function FromName($name)
    {
        $candidates = EVA::GetByProperty("name", $name, "ticket_group");
        if(count($candidates) > 0)
        {
            return new TicketGroup($candidates[0]);
        }
        return;
        
    }
}
