<?php

class KBGroupDBBacker implements IKBGroupBacker
{
    public $table;
    public function __construct($tablename)
    {
        $this->table = $tablename;
    }
    public function Find($id) : int
    {
        $fields = ['collectionId'];
        $q=DBHelper::Select($this->table, $fields, ['entityId'=>$id]);
        $gid = DBHelper::RunScalar($q,[$id]);
        if($gid === false)
        {
            return 0;
        }
        return intval($gid);
    }
    
    public function GetItems($id)
    {
        $fields = ['ordinal','entityId','prev','next'];
        $q=DBHelper::Select($this->table, $fields, ['collectionId'=>$id],['ordinal'=>'ASC']);
        //////var_dump($q);
        //die;
        $items = [];
        $result = DBHelper::RunTable($q,[$id]);
        foreach($result as $row)
        {
            $items[$row['ordinal']] = [
                'id'=>intval($row['entityId']),
                'prev'=>intval($row['prev']) ?? 0,
                'next'=>intval($row['next']) ?? 0];
        }
        ksort($items);
        return $items;
    }
    
    public function SetItems($id,$items)
    {
        
        DBHelper::BeginTransaction();
        // erase current entries
        DBHelper::Delete($this->table,['collectionId'=>$id]);
        // write updated entries
        // #TODO: this, but as one write
        foreach($items as $ord=>$item)
        {
            // always collectionId, ordinal, entityId [, ...]
            $row = [$id,$ord,$item['id'],$item['prev'],$item['next']];
            ////var_dump($row);
            DBHelper::Insert($this->table,$row);
        }
        DBHelper::Commit();
    }
}