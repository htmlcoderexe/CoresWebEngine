<?php


//Module::DemandProperty("attachment","Attachment","BLOB id of attached file");
//Module::DemandProperty("ticket.update.type","Ticket update type","Type of an update attached to a ticket");
/**
 * Description of TicketUpdate
 *
 * @author admin
 */
class TicketUpdate
{
    public function __construct(
        public int $id,
        public int $ticket_id,
        public int $user,
        public int $time,
        public int $type,
        public int $newgroup = -1,
        public string $newtext = "",
        public string $newtitle ="",
        public int $newstate = -1,
        public int $newuser = -1,
        public array $files = []){}
    
    public const TABLE = 'ticket_updates';
    public const FIELDS = ['id',
        'ticket_id',
        'user',
        'time',
        'type',
        'newgroup',
        'newtext',
        'newtitle',
        'newstate',
        'newuser'
    ];
    public const SCHEMA = [
        'ticket_id'=>'INT',
        'user'=>'INT',
        'time'=>'INT',
        'type'=>'INT',
        'newgroup'=>'INT',
        'newtext'=>'TEXT',
        'newtitle'=>'VARCHAR(200)',
        'newstate'=>'INT',
        'newuser'=>'INT'
    ];
    
    public const TYPE_INFO = 0;
    public const TYPE_REASSIGN = 1;
    public const TYPE_STATUSCHANGE = 2;
    public const TYPE_GROUPCHANGE = 3;
    public const TYPE_COMMENT = 4;
    public const TYPE_FULL = 5;
    
    public function EVA__construct($EvaID)
    {
        $e=new EVA($EvaID);
        if(!$e)
        {
            return;
        }
        $this->ticket=$e->attributes['parent_object'];
        $this->text=$e->attributes['description'];
        $this->user=$e->attributes['user_id'];
        $this->type=$e->attributes['ticket.update.type'];
        $this->files=$e->attributes['attachment']??[];
        $this->time=$e->attributes['timestamp'];
        
    }
    
    public static function FromRow(array $row, array $attachments)
    {
        $update = new TicketUpdate(
                id: $row['id'],
                ticket_id: $row['ticket_id'],
                user: $row['user'],
                time: $row['time'],
                type: $row['type'],
                newgroup: $row['newgroup'],
                newtext: $row['newtext'],
                newtitle: $row['newtitle'],
                newstate: $row['newstate'],
                newuser: $row['newuser'],
                files: $attachments
        );
        return $update;
    }
    
    public static function Load(int $id)
    {
        $row = DBHelper::GetRowById(table: self::TABLE, id: $id, fields: self::FIELDS);
        if(!$row)
        {
            return null;
        }
        $attachments = TicketUpdateAttachment::GetAttachmentsForUpdate($id);
        $update = self::FromRow($row, $attachments);
        return $update;
    }
    
    public static function GetUpdates(int $id)
    {
        //$rows = DBHelper::GetRowsByField(table: self::TABLE, field: 'ticket_id', value: $id, fields: self::FIELDS);
        
        $rowq = DBHelper::Select(table: self::TABLE, fields: self::FIELDS, where: ['ticket_id'=>$id], orderby: ['time'=>'ASC']);
        $rows = DBHelper::RunTable($rowq, [$id]);

        if(!$rows)
        {
            return [];
        }
        $updates = [];
        $attachments = TicketUpdateAttachment::GetAttachmentsForTicket($id);
        foreach($rows as $row)
        {
            $files = [];
            if(isset($attachments[$row['id']]))
            {
                $files = $attachments[$row['id']];
            }
            $update = TicketUpdate::FromRow(row: $row, attachments: $files);
            $updates[]=$update;
        }
        return $updates;
    }
    
    public static function Create(
        int $ticket_id, 
        int $user = -1, int $type = self::TYPE_INFO,
        int $newgroup = -1,
        string $newtext = "",
        string $newtitle = "",
        int $newstate = -1,
        int $newuser = -1,
        array $files = [],
        int $time = -1)
    {
        
        if($user == -1)
        {
            $cu=User::GetCurrentUser();
            $user = $cu->userid;
        }
        $now = $time == -1 ? time() : $time;
        $row = [null, $ticket_id, $user, $now, $type, $newgroup, $newtext, $newtitle, $newstate, $newuser];
        DBHelper::Insert(table: self::TABLE, values: $row);
        $id = DBHelper::GetLastId();
        $attachments = [];
        if($files && (count($files) >0))
        {
            foreach($files as $file)
            {
                $attachment = TicketUpdateAttachment::Create(ticket_id: $ticket_id, update_id: $id, blobid: $file);
                if($attachment)
                {
                    $attachments[]=$attachment;
                }
            }
        }
        $update = new TicketUpdate(
                id: $id,
                ticket_id: $ticket_id, user: $user, time: $now, type: $type,
                newgroup: $newgroup, newtext: $newtext, newtitle: $newtitle, newstate: $newstate, newuser: $newuser,
                files: $attachments
        );
        return $update;
    }
    
    /**
     * Creates and posts a ticket update.
     * @param int $parent Ticket's root object ID
     * @param string $text Update's text
     * @param int $user UID associated with the update
     * @param string $type Update type
     * @param array $files a slice of PHP's $_FILES array
     * @return TicketUpdate the resulting ticket update
     */
    public static function EVA_Create($parent,$text,$user,$type="info",$files=[])
    {
        $e= EVA::CreateObject("ticket.update",EVA::OWNER_NOBODY,["description"=>$text,"user_id"=>$user,"ticket.update.type"=>$type,"parent_object"=>$parent,"timestamp"=>time()]);
        // check if the array is actually usable
        if(isset($files['name']))
        {
            for($i = 0; $i < count($files['name']); $i++)
            {
                $file = File::Upload($files, $i);
                if($file)
                {
                    $e->AddAttribute("attachment", $file->blobid);
                }
                else
                {
                    EngineCore::WriteUserError("Uploading \"" . $files['name'][$i] . "\" failed.", "upload");
                    Logger::Log("Was unable to upload \"" . $files['name'][$i] . "\".", 0, "upload error");
                }
            }
        }
        $e->Save();
        return new TicketUpdate($e->id);
    }
}
