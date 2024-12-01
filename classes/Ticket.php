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
Module::DemandProperty("attachment","Attachment","BLOB id of attached file");
Module::DemandProperty("ticket.update.type","Ticket update type","Type of an update attached to a ticket");
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
    
    public $Updates=[];
    
    public const TYPE_INC=0;
    public const TYPE_REQ=1;
    public const TYPE_PRJ=2;
    
    public const TICKET_CODES =["INC","REQ","PRJ"];
    
    public const STATUS_OPEN=0;
    public const STATUS_INPROGRESS=1;
    public const STATUS_WAIT_CHILD=2;
    public const STATUS_ONHOLD_TIME=3;
    public const STATUS_ONHOLD_INDEFINITE=4;
    public const STATUS_WAIT_FIX=5;
    public const STATUS_CLOSED=6;
    
    public const TICKET_STATUSES=[
        "New",
        "In Progress",
        "Awaiting task",
        "Postponed",
        "Frozen",
        "Resolved",
        "Closed"
    ];
    
    public const TICKET_NUMBER_LENGTH=6;
    
    
    /**
     * Loads a ticket given its canonical number
     * @param string $ticketID ticket number in canonical format
     * @return type Ticket the ticket if it was found, else null
     */
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
        $this->EvaId=$ticketresult['EvaID'];;
        $this->Type=$type;
        $this->Title=$ticketresult['title'];
        $this->Description=$ticketresult['description'];
        $this->Submitter=$ticketresult['submitter'];
        $this->GetState();
    }
    
    /**
     * Refresh the ticket's state from DB and return it as well
     * @return string, I think
     */
    public function GetState()
    {
        $q=DBHelper::Select("ticket_state_changes", ["newstate"], ["ticketid"=>$this->Id],["time"=>"DESC"],1);
        $this->CurrentState=DBHelper::RunScalar($q, [$this->Id]);
        return $this->CurrentState;
    }
    
    /**
     * Set the ticket's state
     * @param int(technically) $state the new state code
     * seriously can we get it on with the enums
     */
    public function ChangeState($state)
    {
        DBHelper::Insert("ticket_state_changes",[null, $this->Id,$state,time()]);
        $this->CurrentState=$state;
        if($state==self::STATUS_CLOSED)
        {   
            $this->Done=time();
            DBHelper::Update("tickets",["completedtime"=>time()],["id"=>$this->Id]);
        }
        
    }
    /**
     * Close the ticket (wait, really?)
     */
    public function Close()
    {
        $this->ChangeState(self::STATUS_CLOSED);
        $this->Done=true;
    }
    /**
     * Get the ticket's canonical number ("INC080085");
     * @return string
     */
    public function GetNumber()
    {
        return self::MakeTicketNumber($this->Type,$this->Id);
    }
    
    public function GetUpdates()
    {
        $this->Updates=[];
        $updateIds=EVA::GetByProperty("parent_object", $this->EvaId, "ticket.update");
        foreach($updateIds as $update)
        {
            $e=new EVA($update);
            if($e)
            {
                $this->Updates[]=$e;
            }
        }
    }
    
    public function AppendUpdate()
    {
        
    }
    
    
    /*------------------\
    |*                  |
    |   Static methods  |
    |                   |
     \-----------------*/
    
    
    
    /**
     * 
     * @param type $title
     * @param type $description
     * @param type $type
     * @param type $category
     * @param type $target
     * @return type
     */
    public static function Create($title,$description,$type,$category=0,$target=-1)
    {
        $cu=User::GetCurrentUser();
        $e=EVA::CreateObject("ticket",EVA::OWNER_CURRENT);
        $insert=[null,$type,$cu->userid,$target==-1?$cu->userid:$target,$e->id,$title,$description,time(),$category,0,$cu->userid];
        DBHelper::Insert("tickets",$insert);
        $tid=DBHelper::GetLastId();
        $stateinsert=[null,$tid,self::STATUS_OPEN,time()];
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
    public static function ReadableStatusName($status)
    {
        return self::TICKET_STATUSES[$status % count(self::TICKET_STATUSES)];
    }
}

