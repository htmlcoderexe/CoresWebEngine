<?php

/**
 * Description of TicketInfo
 *
 * @author admin
 */
class TicketInfo
{
    
    public function __construct(
            public int $id,
            public int $type,
            public string $title,
            public string $description,
            public int $submitter,
            public int $subject,
            public int $owner,
            public int $time,
            public int $completed_time,
            public int $last_status,
            public int $last_update,
            public int $group
    ){}
    
    public const TABLE = 'tickets';
    public const FIELDS = ["id",
        "type","title","description",
        "submitter","subject","owner",
        "time","completed_time",
        "last_status",
        "last_update",
        "agroup"];
    public const SCHEMA = [
        "type"=>'INT',"title"=>'VARCHAR(200)',"description"=>'TEXT',
        "submitter"=>'INT',"subject"=>'INT',"owner"=>'INT',
        "time"=>'INT',"completed_time"=>'INT',
        "last_status"=>'INT',
        "last_update"=>'INT',
        "agroup"=>'INT'];
    
    
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
    
    public static function FromRow($row) : TicketInfo | null
    {
        $info = new TicketInfo(
                id: $row['id'],
                type: $row['type'],
                title: $row['title'],
                description: $row['description'],
                submitter: $row['submitter'],
                subject: $row['subject'],
                owner: $row['owner'],
                time: $row['time'],
                completed_time: $row['completed_time'],
                last_status: $row['last_status'],
                last_update: $row['last_update'],
                group: $row['agroup']
        );
        return $info;
    }
    
    public static function Load($id) : TicketInfo | null
    {
        $row = DBHelper::GetRowById(table: self::TABLE, id: $id, fields: self::FIELDS);
        if(!$row)
        {
            return null;
        }
        $info = self::FromRow($row);
        return $info;
    }
    
    public static function Create(string $title, string $description, int $submitter, int $owner, int $type = self::TYPE_INC, int $subject = -1, int $group = 0, int $time = -1)
    {
        if($subject == -1)
        {
            $subject = $submitter;
        }
        $now = $time == -1 ? time() : $time;
        $row = [null, $type, $title, $description, $submitter, $subject, $owner, $now, 0, self::STATUS_OPEN, $now, $group];
        DBHelper::Insert(table: self::TABLE, values: $row);
        $id = DBHelper::GetLastId();
        $info = new TicketInfo(id: $id, 
                type: $type, title: $title, description: $description, 
                submitter: $submitter, subject: $subject, owner: $owner,
                time: $now, completed_time: 0, last_status: self::STATUS_OPEN, last_update: $now, group: $group);
        return $info;
    }
    
    public function Update(bool $no_auto_time = false)
    {
        if(!$no_auto_time)
        {
            $this->time = time();
        }
        $update = [
        "type"=>$this->type,"title"=>$this->title,"description"=>$this->description,
        "submitter"=>$this->submitter,"subject"=>$this->subject,"owner"=>$this->owner,
        "time"=>$this->time,"completed_time"=>$this->completed_time,
        "last_status"=>$this->last_status,
        "last_update"=>$this->last_update,
        "agroup"=>$this->group];
        DBHelper::Update(table: self::TABLE, where: ['id'=>$this->id], assignments: $update);
    }
    
    
    public static function GetTickets(int $gid = -1)
    {
        $where = [];
        $p = [];
        if($gid!=-1)
        {
            $where = ['agroup'=>$gid];
            $p = [$gid];
        }
        $ticketq = DBHelper::Select(table: self::TABLE, fields: self::FIELDS,where: $where, orderby: ['last_update'=>'DESC']);
        $rows = DBHelper::RunTable($ticketq, $p);
        $tickets = [];
        foreach($rows as $row)
        {
            $ticket = TicketInfo::FromRow($row);
            $tickets[]=$ticket;
        }
        return $tickets;
    }
}
