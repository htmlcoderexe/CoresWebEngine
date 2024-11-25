<?php

$structure_tickets=[
    "type"=>"int",
    "submitter"=>"int",
    "subject"=>"int",
    "EvaID"=>"int",
    "title"=>"varchar(255)",
    "description"=>"varchar(3000)",
    "time"=>"int",
    "category"=>"int",
    "completedtime"=>"int",
    "owner"=>"int"
];
$structure_states=[
    "ticketid"=>"int",
    "newstate"=>"int",
    "time"=>"int"
];

Module::DemandTable("tickets", $structure_tickets);
Module::DemandTable("ticket_state_changes",$structure_states);

/**
 * Description of Ticket
 *
 * @author admin
 */
class Ticket
{
    public $Id;
    public $Type;
    public $EvaId;
    
    public $Submitter;
    public $Owner;
    public $Target;
    
    
    public $Title;
    public $Description;
    public $Category;
    
    public $Created;
    public $Done;
    public $CurrentState;
    
    
    public const TYPE_INC=0;
    public const TYPE_REQ=1;
    public const TYPE_PRJ=2;
    
    public const TICKET_CODES =["INC","REQ","PRJ"];
    
    public const TICKET_NUMBER_LENGTH=6;
    
    public function __construct($ticketID)
    {
        list($type,$id)=self::ParseTicketNumber($ticketID);
        $filters=["id"=>$id];
        $q=DBHelper::Select("tickets", ["id","type","subject","EvaID","title","submitter","description"], $filters);
        $ticketresult=DBHelper::RunRow($q,array_values($filters));
        if(!$ticketresult)
        {
            return;
        }
        $this->Id=$id;
        $this->Type=$type;
        $this->Title=$ticketresult['title'];
        $this->Description=$ticketresult['description'];
        $this->Submitter=$ticketresult['submitter'];
    }
    
    public function GetState()
    {
        $q=DBHelper::Select("tickets", ["newstate"], ["ticketid"=>$this->Id],["time"=>"DESC"],1);
        $this->CurrentState=DBHelper::RunScalar($q, [$this->Id]);
    }
    
    public function GetNumber()
    {
        return self::MakeTicketNumber($this->Type,$this->Id);
    }
    
    public static function Create($title,$description,$type,$category=0,$target=-1,)
    {
        $cu=User::GetCurrentUser();
        $e=EVA::CreateObject("ticket",EVA::OWNER_CURRENT);
        $insert=[null,$type,$cu->userid,$target==-1?$cu->userid:$target,$e->id,$title,$description,time(),$category,0,$cu->userid];
        DBHelper::Insert("tickets",$insert);
        $tid=DBHelper::GetLastId();
        $stateinsert=[null,$tid,0,time()];
        DBHelper::Insert("ticket_state_changes",$stateinsert);
        return self::MakeTicketNumber($type,$tid);
    }
    
    public static function ParseTicketNumber($number)
    {
        $code=substr($number,0,3);
        $id=intval(substr($number,3));
        $type=array_search($code,self::TICKET_CODES);
        if($type===false)
        {
            $type=-1;
        }
        return [$type,$id];
    }
    public static function MakeTicketNumber($type,$id)
    {
        $code="XXX";
        if(isset(self::TICKET_CODES[$type]))
        {
            $code=self::TICKET_CODES[$type];
        }
        $number=sprintf("%0".self::TICKET_NUMBER_LENGTH."d",$id);
        return $code.$number;
    }
}

