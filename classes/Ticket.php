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

//Module::DemandTable("tickets", $structure_tickets);
//Module::DemandTable("ticket_state_changes",$structure_states);
//Module::DemandProperty("attachment","Attachment","BLOB id of attached file");
//Module::DemandProperty("ticket.update.type","Ticket update type","Type of an update attached to a ticket");
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
    
    //public $Updates=[];
    
    
    public function __construct(
            public string $id,
            public TicketInfo $info,
            public array $updates
    ){}
    
    /**
     * Loads a ticket given its canonical number
     * @param string $ticketID ticket number in canonical format
     * @return type Ticket the ticket if it was found, else null
     */
    public function EVA__construct($ticketID)
    {
        list($type,$id)=self::ParseTicketNumber($ticketID);
        $filters=["id"=>$id];
        $q=DBHelper::Select("tickets", ["id","type","subject","EvaID","title","submitter","description","category"], $filters);
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
        $this->Category=$ticketresult['category'];
        $this->GetState();
    }
    
    public static function Load(string $id) : Ticket | null
    {
        list($type,$ticket_id)=self::ParseTicketNumber($id);
        $info = TicketInfo::Load(id: $ticket_id);
        if(!$info)
        {
            return null;
        }
        $updates = TicketUpdate::GetUpdates(id: $ticket_id);
        $ticket = new Ticket(id: $id, info: $info, updates: $updates);
        return $ticket;
    }
    
    public static function Create(string $title, string $description, int $type, int $user = -1, int $submitter = -1, int $subject = -1, array $attachments = [], int $time = -1)
    {
        $info = TicketInfo::Create(
                title: $title, description: $description,
                submitter: $submitter,subject: $subject, owner: $user,
                type: $type, group: 0, time: $time);
        $update = TicketUpdate::Create(ticket_id: $info->id, user: $submitter, type: TicketUpdate::TYPE_FULL,
                newgroup: -1,newtext: $description, newtitle: $title, newstate: TicketInfo::STATUS_OPEN, newuser: $user, files: $attachments, time: $time);
        $ticket = new Ticket(id: self::MakeTicketNumber($info->type,$info->id), info: $info, updates: [$update]);
        return $ticket;
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
    public function ChangeState(int $state, int $user = -1)
    {
        /*
        DBHelper::Insert("ticket_state_changes",[null, $this->Id,$state,time()]);
        $this->CurrentState=$state;
        if($state==self::STATUS_CLOSED)
        {   
            $this->Done=time();
            DBHelper::Update("tickets",["completedtime"=>time()],["id"=>$this->Id]);
        }
         * 
         */
        $update = TicketUpdate::Create(ticket_id: $this->info->id, user: $user, type: TicketUpdate::TYPE_STATUSCHANGE, newstate: $state);
        $this->updates[]= $update;
        $this->info->last_status = $state;
        if($state == TicketInfo::STATUS_CLOSED)
        {
            $this->info->completed_time = time();
        }
        $this->info->Update();
    }
    /**
     * Close the ticket (wait, really?)
     */
    public function Close()
    {
        $this->ChangeState(TicketInfo::STATUS_CLOSED);
    }
    /**
     * Get the ticket's canonical number ("INC080085");
     * @return string
     */
    public function GetNumber()
    {
        return self::MakeTicketNumber($this->info->type,$this->info->id);
    }
    
    public function GetUpdates()
    {
        $this->Updates=[];
        $updatecount=0;
        $updateIds=EVA::GetByProperty("parent_object", $this->EvaId, "ticket.update");
        foreach($updateIds as $update)
        {
            $e=new TicketUpdate($update);
            if($e->ticket)
            {
                $this->Updates[]=$e;
                $updatecount++;
            }
        }
        return $updatecount;
    }
    
    public function AppendCommentUpdate(string $text,int $user = -1,$files=[])
    {
        //$update=TicketUpdate::Create($this->EvaId, $text, $user, $type, $files);
        $update = TicketUpdate::Create(ticket_id: $this->info->id, user: $user, type: TicketUpdate::TYPE_COMMENT, newtext: $text, files: $files);
        $this->Updates[]=$update;
    }
    
    public function AssignGroup(int $gid, int $user = -1, int $newuser = -1)
    {
        /**
        $this->Category=$gid;
         DBHelper::Update("tickets",["category"=>$gid],["id"=>$this->Id]);
         * 
         */
        $update = TicketUpdate::Create(ticket_id: $this->info->id, user: $user, type: TicketUpdate::TYPE_GROUPCHANGE, newgroup: $gid, newuser: $newuser);
        $this->updates[]=$update;
        $this->info->group = $gid;
        if($newuser != -1)
        {
            $this->info->owner = $newuser;
        }
        $this->info->Update();
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
    public static function EVA_Create($title,$description,$type,$category=0,$target=-1)
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
        $type=array_search($code,TicketInfo::TICKET_CODES);
        if($type===false)
        {
            $type=-1;
        }
        return [$type,$id];
    }
    public static function MakeTicketNumber($type,$id)
    {
        $code="XXX";
        if(isset(TicketInfo::TICKET_CODES[$type]))
        {
            $code=TicketInfo::TICKET_CODES[$type];
        }
        $number=sprintf("%0".TicketInfo::TICKET_NUMBER_LENGTH."d",$id);
        return $code.$number;
    }
    public static function ReadableStatusName($status)
    {
        return TicketInfo::TICKET_STATUSES[$status % count(TicketInfo::TICKET_STATUSES)];
    }
}

