<?php

/**
 * Description of Document
 *
 * @author admin
 */
class Document
{
    public const TABLE = 'documents';
    public const SCHEMA = [
        "title"=>"VARCHAR(255)",
        "type"=>"INT",
        "description"=>"TEXT",
        "visibility"=>"INT",
        "thumbnail"=>"VARCHAR(100)",
        "uid"=>"INT",
        "gid"=>"INT"
    ];
    public const FIELDS = ['id',
        "title",
        "type",
        "description",
        "visibility",
        "thumbnail",
        "uid",
        "gid"
    ];
    
    public const SENSITIVITY_PUBLIC = 0;
    public const SENSITIVITY_GROUP = 1;
    public const SENSITIVITY_PRIVATE = 2;
    public const SENSITIVITY_SECRET = 3;
    
    public const TYPE_UNKNOWN = 0;
    public const TYPE_BOOK = 1;
    public const TYPE_MANUAL = 2;
    public const TYPE_WHITEPAPER = 3;
    public const TYPE_EVENT = 4;
    public const TYPE_ADMINISTRATIVE = 5;
    public const TYPE_RECEIPT = 6;
    public const TYPE_CERT = 7;
    public const TYPE_REFERENCE = 8;
    
    function __construct(
        public int $id,
        public string $title, 
        public string $description = "", 
        public int $doctype = self::TYPE_UNKNOWN,
        public int $visibility = self::SENSITIVITY_PUBLIC, 
        public int $owner = EVA::OWNER_NOBODY, 
        public array $files = [],
        public string $thumbnail = ""
    ){}
    
    public static function Load(int $id)
    {
        $fields = [
            'title','type','description','visibility','thumbnail','uid'
        ];
        $select = DBHelper::Select(self::TABLE, $fields, ['id'=>$id]);
        $result = DBHelper::RunRow($select, [$id]);
        if(!$result)
        {
            return null;
        }
        $files = DocumentFile::GetFiles($id);
        
        $doc = new Document(
                id: $id, title: $result['title'], description: $result['description'],
                doctype: $result['type'], visibility: $result['visibility'],
                owner: $result['uid'], files: $files, thumbnail: $result['thumbnail']);
        return $doc;
    }
    
    
    public static function Create(
        string $title, 
        string $description = "", 
        int $doctype = self::TYPE_UNKNOWN,
        int $visibility = self::SENSITIVITY_PUBLIC, 
        int $owner = EVA::OWNER_NOBODY, 
        array $filelist = [],
        string $thumbnail = ""
    )
    {
        $row = [
            null, $title, $doctype, $description,
            $visibility,
            $thumbnail,
            $owner, 0
        ];
        DBHelper::Insert(self::TABLE, $row);
        $docId = DBHelper::GetLastId();
        $files = [];
        foreach($filelist as $blobid)
        {
            $docfile = DocumentFile::Create($docId, $blobid);
            if(!$docfile)
            {
                continue;
            }
            $files[]=$docfile;
        }
        $doc = new Document(
                id: $docId, title: $title, description: $description,
                doctype: $doctype, visibility: $visibility,
                owner: $owner, files: $files, thumbnail: $thumbnail);
        return $doc;
    }
    
    public static function GetAll(int $userid = -1, int $type = -1)
    {
        $docs = [];
        $filters = [];
        $p = [];
        if($type!=-1)
        {
            $filters['type']=$type;
            $p[]=$type;
        }
        $pubwhere = ['visibility'=>self::SENSITIVITY_PUBLIC];
        $pubwhere = array_merge($pubwhere, $filters);
        $pubp = [self::SENSITIVITY_PUBLIC];
        $pubp = array_merge($pubp, $p);
        $q= DBHelper::Select(table: self::TABLE, fields: self::FIELDS, where: $pubwhere);
        $pubdocs = DBHelper::RunTable($q, $pubp);
        $docs = array_merge($docs, $pubdocs);
        if($userid!=-1)
        {
            $mywhere = ['visibility'=>self::SENSITIVITY_PRIVATE, 'uid'=>$userid];
            $mywhere = array_merge($mywhere, $filters);
            $myp = [self::SENSITIVITY_PRIVATE,$userid];
            $myp = array_merge($myp, $p);
            $q=DBHelper::Select(table: self::TABLE, fields: self::FIELDS, where: $mywhere);
            $mydocs = DBHelper::RunTable($q,$myp);
            $docs = array_merge($docs, $mydocs);
        }
        return $docs;
    }
}
