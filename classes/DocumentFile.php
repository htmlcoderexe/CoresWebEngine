<?php

$docfiletable = [
    "document_id" => "INT",
    "blobid" => "VARCHAR(100)",
    "format" => "VARCHAR(20)"
    ];
Module::DemandTable(DocumentFile::TABLE,$docfiletable);

/**
 * Description of DocumentFile
 *
 * @author admin
 */
class DocumentFile
{
    
    public const TABLE = 'document_files';
    
    public function __construct(
            public int $id,
            public int $document_id,
            public string $blobid,
            public string $format){}
            
    public static function GetFiles($id) : array
    {
        $fields = ['id','document_id','blobid','format'];
        $select = DBHelper::Select(self::TABLE, $fields, ['document_id'=>$id]);
        $result = DBHelper::RunTable($select, [$id]);
        if(!$result)
        {
            return [];
        }
        $files = [];
        foreach($result as $row)
        {
            $file = new DocumentFile(id: $row['id'], document_id: $row['document_id'], blobid: $row['blobid'], format: $row['format']);
            $files[]=$file;
        }
        return $files;
    }
    public static function Create(int $documentId, string $blobid)
    {
        $file = File::Load(blobid: $blobid);
        if(!$file)
        {
            return null;
        }
        $ext = $file->filext;
        $filerow = [
            null, $documentId, $blobid, $ext
        ];
        DBHelper::Insert(self::TABLE, $filerow);
        $docfileId = DBHelper::GetLastId();
        $docfile = new DocumentFile(id: $docfileId, blobid: $blobid, format: $ext, document_id: $documentId);
        return $docfile;
    }
}
