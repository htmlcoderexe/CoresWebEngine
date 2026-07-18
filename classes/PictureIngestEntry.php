<?php

/**
 * Description of PictureIngestEntry
 *
 */
class PictureIngestEntry
{
    /**
     * Creates an instance of an ingest entry
     * @param int $ingest_id PictureIngest the picture belongs to
     * @param int $picture_id Picture ID
     */
    public function __construct(
            public int $ingest_id,
            public int $picture_id
    ){}
    
    public const TABLE = 'picture_ingest_entries';
    public const FIELDS = ['id',
        'ingest_id',
        'picture_id'];
    public const SCHEMA = [
        'ingest_id'=>'INT',
        'picture_id'=>'INT'];
    
    public static function Create( int $ingest_id, int $picture_id) : PictureIngestEntry
    {
        DBHelper::Insert(table:self::TABLE, values:[$ingest_id, $picture_id]);
        return new PictureIngestEntry(ingest_id: $ingest_id, picture_id: $picture_id);
    }
    
    public static function Delete( int $ingest_id, int $picture_id)
    {
        DBHelper::Delete(table:self::TABLE, where:['ingest_id'=>$ingest_id, 'picture_id'=>$picture_id]);
    }
}
