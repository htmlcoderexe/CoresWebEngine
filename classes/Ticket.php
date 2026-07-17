<?php

/**
 * Represents a Ticket
 *
 */
class Ticket
{
    /**
     * Creates an instance of Ticket
     * @param string $id Readable ticket ID such as INC000001
     * @param TicketInfo $info Object containing ticket's information 
     * @param array $updates Array of TicketUpdate containing updates to the ticket
     */
    public function __construct(
            public string $id,
            public TicketInfo $info,
            public array $updates
    ){}
    
    
    /**
     * Loads a ticket given its canonical number
     * @param string $id ticket number in canonical format
     * @return type Ticket the ticket if it was found, else null
     */
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
     * Set the ticket's state
     * @param int(technically) $state the new state code
     * seriously can we get it on with the enums
     */
    public function ChangeState(int $state, int $user = -1)
    {

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
    
    /**
     * Appends a comment with optional attached files
     * @param string $text Comment text
     * @param int $user User adding the comment, defaults to current user
     * @param array $files Optional array of File blob IDs to attach
     */
    public function AppendCommentUpdate(string $text,int $user = -1, array $files=[])
    {
        $update = TicketUpdate::Create(ticket_id: $this->info->id, user: $user, type: TicketUpdate::TYPE_COMMENT, newtext: $text, files: $files);
        $this->Updates[]=$update;
    }
    
    /**
     * Assigns the ticket to a group
     * @param int $gid Group ID
     * @param int $user Assigning user, defaults to current user
     * @param int $newuser Optional new owner
     */
    public function AssignGroup(int $gid, int $user = -1, int $newuser = -1)
    {
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
     * Converts a ticket number into a type and ID
     * @param string $number Ticket number in the INC000001 format
     * @return array [string type, int id]
     */
    public static function ParseTicketNumber(string $number) : array
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
    
    /**
     * Creates a readable ticket number in the INC000001 format out of a numeric type and ID
     * @param int $type Ticket type, must be in the TicketInfo::TICKET_CODES array
     * @param int $id Ticket ID
     * @return string The ticket number
     */
    public static function MakeTicketNumber(int $type, int $id) : string
    {
        $code="XXX";
        if(isset(TicketInfo::TICKET_CODES[$type]))
        {
            $code=TicketInfo::TICKET_CODES[$type];
        }
        $number=sprintf("%0".TicketInfo::TICKET_NUMBER_LENGTH."d",$id);
        return $code.$number;
    }
    
    /**
     * Converts a numeric status to a string
     * @param int $status Status number
     * @return string Readable ticket status from TicketInfo::TICKET_STATUSES
     */
    public static function ReadableStatusName(int $status) : string
    {
        return TicketInfo::TICKET_STATUSES[$status % count(TicketInfo::TICKET_STATUSES)];
    }
}
