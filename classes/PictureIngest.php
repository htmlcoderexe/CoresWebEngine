<?php

/**
 * Description of PictureIngest
 *
 * @author admin
 */
class PictureIngest
{
    public const TABLE = "picture_ingest_folders";
    
    public const FIELDS = ['id',
        'folder',
        'visibility',
        'active'
        ];
    
    public const SCHEMA = [
        'folder'=>'VARCHAR(200)',
        'visibility'=>'INT',
        'active'=>'INT'
        ];
    
    public const VISIBILITY_OWNER=0;
    public const VISIBILITY_GROUP=1;
    public const VISIBILITY_USERS=2;
    public const VISIBILITY_PUBLIC=3;
    
    public const PICTURE_INGEST_DIR="pictures";
    
    
    
    /**
     * Creates an instance of PictureIngest
     * @param int $id Ingest ID
     * @param string $folder Folder that contains the files
     * @param int $visibility Visibility level of the resulting images
     * @param bool $active True if ingest is active
     */
    public function __construct(
        public int $id,
        public string $folder,
        public int $visibility,
        public bool $active
    ){}
    
    public static function Load(int $id) : PictureIngest|null
    {
        $row = DBHelper::GetRowById(table: self::TABLE, id: $id, fields: self::FIELDS);
        if(!$row)
        {
            return null;
        }
        $ingest = new PictureIngest(id: $row['id'], folder: $row['folder'], visibility: $row['visibility'], active: $row['active'] == 1);
        return $ingest;
    }
    
    public static function Create(string $folder, int $visibility, bool $active=true)
    {
        $row = [null, $folder, $visibility, $active?1:0];
        DBHelper::Insert(table: self::TABLE, values: $row);
        $id = DBHelper::GetLastId();
        $ingest = new PictureIngest(id: $id, folder: $folder, visibility: $visibility, active: $active);
        return $ingest;
    }
    
    /**
     * Gets all pictures linked to an ingest
     * @param int $id Ingest ID
     * @returns array List of picture IDs
     */
    public static function GetPictures(int $id)
    {
        $q = DBHelper::Select(table: PictureIngestEntry::TABLE, fields: ['picture_id'], where: ['ingest_id'=>$id]);
        $list = DBHelper::RunList(query: $q, params: [$id]);
        return $list;
    }
    
    public static function GetIngests(bool $active_only = false)
    {
        $where = [];
        $p = [];
        if($active_only)
        {
            $where = ['active'=>1];
            $p = [1];
        }
        $q = DBHelper::Select(table: self::TABLE, fields: self::FIELDS, where: $where);
        $rows = DBHelper::RunTable($q, $p);
        $ingests = [];
        foreach($rows as $row)
        {
            $ingests[]=new PictureIngest(id: $row['id'], folder: $row['folder'], visibility: $row['visibility'], active: $row['active'] == 1);
        }
        return $ingests;
    }
    
    public function Save()
    {
        $update = ['folder'=>$this->folder, 'active'=>$this->active?1:0,'visibility'=>$this->visibility];
        DBHelper::Update(table: self::TABLE, assignments: $update, where: ['id'=>$this->id]);
        return;
    }
    
    public function Run()
    {
        User::SetSU(User::GetCurrentUser()->username);
        $result = Picture::Ingest(self::PICTURE_INGEST_DIR . DIRECTORY_SEPARATOR . $this->folder);
        // regular ingest results, false is STOP, true is CONTINUE
        if($result === true || $result === false)
        {
            return $result;
        }
        // otherwise, a picture was ingested
        //DBHelper::Insert("eva_mappings",["picture.ingest", "picture", $this->id, $result->id]);
        PictureIngestEntry::Create(ingest_id: $this->id, picture_id: $result->id);
        User::ClearSU();
        return true;
    }
    
}
