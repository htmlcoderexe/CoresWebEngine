<?php

/**
 * Represents a ticket update
 *
 */
class TicketUpdate
{
    /**
     * Creates an insance of TicketUpdate
     * @param int $id Update ID
     * @param int $ticket_id Ticket ID
     * @param int $user User creating the update
     * @param int $time Time of the update
     * @param int $type Update type
     * @param int $newgroup Group changed by update, if applicable
     * @param string $newtext Comment text
     * @param string $newtitle Title changed by update, if applicable
     * @param int $newstate Ticket state changed by update, if applicable
     * @param int $newuser User the ticket is reassigned to, if applicable
     * @param array $files Attachments as an array of File blobids, if applicable
     */
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
    
    /**
     * Creates an instance of TicketUpdate from a database row and a list of attachments
     * @param array $row An associative array, for example, a database row, with the relevant fields
     * @param array $attachments A string[] of blobids
     * @return TicketUpdate|null
     */
    public static function FromRow(array $row, array $attachments) : TicketUpdate | null
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
    
    /**
     * Loads a TicketUpdate by ID
     * @param int $id Update ID
     * @return TicketUpdate|null
     */
    public static function Load(int $id) : TicketUpdate | null
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
    
    /**
     * Fetches updates for a given ticket, sorted from earliest to latest
     * @param int $id Ticket ID
     * @return array An array of TicketUpdate
     */
    public static function GetUpdates(int $id) : array
    {
        
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
    
    /**
     * Creates a new update
     * @param int $ticket_id Ticket ID
     * @param int $user User creating the update
     * @param int $type Update type
     * @param int $newgroup Group changed by update, if applicable
     * @param string $newtext Comment text
     * @param string $newtitle Title changed by update, if applicable
     * @param int $newstate Ticket state changed by update, if applicable
     * @param int $newuser User the ticket is reassigned to, if applicable
     * @param array $files Attachments as an array of File blobids, if applicable
     * @param int $time Time of the update
     * @return \TicketUpdate
     */
    public static function Create(
        int $ticket_id, 
        int $user = -1, 
        int $type = self::TYPE_INFO,
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
    
}
