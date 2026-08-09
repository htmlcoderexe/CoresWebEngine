<?php


/**
 * Represents a single file attached to an update
 *
 */

class TicketUpdateAttachment
{
    public const TABLE = "ticket_attachments";
    public const SCHEMA = [
        'update_id'=>'INT',
        'ticket_id'=>'INT',
        'blobid'=>'VARCHAR(100)',
        'format'=>'VARCHAR(20)',
        'size'=>'INT'
    ];
    public const FIELDS = [
        'id',
        'update_id',
        'ticket_id',
        'blobid',
        'format',
        'size'
    ];
    
    /**
     * Creates an instance of TicketUpdateAttachment
     * @param int $id Attachment ID
     * @param int $update_id Update ID
     * @param int $ticket_id Ticket ID - cached value to speed up loading
     * @param string $blobid Attachment blobid
     * @param string $format File extension of the attachment
     * @param int $size File size of the attachment
     */
    public function __construct(
            public int $id,
            public int $update_id,
            public int $ticket_id,
            public string $blobid,
            public string $format,
            public int $size
    ){}
    
    /**
     * Creates an instance of TicketUpdateAttachment from a database row
     * @param array $row An associative array containing the data
     * @return TicketUpdateAttachment
     */
    public static function FromRow(array $row) : TicketUpdateAttachment
    {
        $attachment = new TicketUpdateAttachment(
                id: $row['id'],
                update_id: $row['update_id'],
                ticket_id: $row['ticket_id'],
                blobid: $row['blobid'],
                format: $row['format'],
                size: $row['size']
        );
        return $attachment;
    }
    
    /**
     * Loads an attachment by ID
     * @param int $id Attahcment ID
     * @return TicketUpdateAttachment|null
     */
    public static function Load(int $id) : TicketUpdateAttachment | null
    {
        $row = DBHelper::GetRowById(self::TABLE, $id, self::FIELDS);
        if(!$row)
        {
            return null;
        }
        return TicketUpdateAttachment::FromRow($row);
    }
    
    /**
     * Creates a new attachment object
     * @param int $ticket_id Ticket ID
     * @param int $update_id Update ID
     * @param string $blobid File blobid
     * @return TicketUpdateAttachment|null
     */
    public static function Create(int $ticket_id, int $update_id, string $blobid) : TicketUpdateAttachment | null
    {
        $file = File::Load($blobid);
        if(!$file)
        {
            return null;
        }
        $row = [null, $update_id, $ticket_id, $blobid, $file->filext, $file->size];
        DBHelper::Insert(self::TABLE, $row);
        $id = DBHelper::GetLastId();
        $attachment = new TicketUpdateAttachment(
                id: $id,
                update_id: $update_id,
                ticket_id: $ticket_id,
                blobid: $blobid,
                format: $file->filext,
                size: $file->size
        );
        return $attachment;
    }
    
    /**
     * Gets all attachments for a TicketUpdate
     * @param int $id Update ID
     * @return array An array of TicketUpdateAttachment
     */
    public static function GetAttachmentsForUpdate(int $id) : array
    {
        $result = DBHelper::GetRowsByField(table: self::TABLE, fields: self::FIELDS, field: 'update_id', value: $id);
        if(!$result)
        {
            return [];
        }
        $files = [];
        foreach($result as $row)
        {
            $file = self::FromRow($row);
            if(!$file)
            {
                continue;
            }
            $files[]=$file;
        }
        return $files;
    }
    
    /**
     * Gets all attachments of all updates of a Ticket
     * @param int $id Ticket ID
     * @return array An array of TicketUpdateAttachment
     */
    public static function GetAttachmentsForTicket(int $id) : array
    {
        $result = DBHelper::GetRowsByField(table: self::TABLE, fields: self::FIELDS, field: 'ticket_id', value: $id);
        if(!$result)
        {
            return [];
        }
        $files = [];
        foreach($result as $row)
        {
            $file = self::FromRow($row);
            if(!$file)
            {
                continue;
            }
            $update_id = $file->update_id;
            if(!isset($files[$update_id]))
            {
                $files[$update_id] = [];
            }
            $files[$update_id][]=$file;
        }
        return $files;
    }
}
