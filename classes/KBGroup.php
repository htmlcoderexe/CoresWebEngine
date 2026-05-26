<?php
require_once "KBGroupMoveResult.php";

interface IKBGroupBacker
{
    /**
     * Fetches a group's items
     * @param type $id
     */
    public function GetItems($id);
    /**
     * Saves a group's items
     * @param type $id
     * @param item[] $items
     */
    public function SetItems($id, $items);
    /**
     * Locates a group by item ID
     * @param type $id
     */
    public function Find($id) : int;
}


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

class KBGroup
{
    public $items;
    public $id;
    public IKBGroupBacker $backer;
    public function __construct(IKBGroupBacker $backer, $id,$items)
    {
        $this->id=$id;
        $this->items = $items;
        $this->backer = $backer;
    }
    public static function Load(IKBGroupBacker $backer, $id)
    {
        $items = $backer->GetItems($id);
        if(count($items)>0)
        {
            return new KBGroup($backer, $id, $items);
        }
    }
    public function Save()
    {
        $this->backer->SetItems(id: $this->id, items: $this->items);
        }
    public static function Find(IKBGroupBacker $backer, $id) : int
    {
        $gid = $backer->Find($id);
        return $gid;
    }
    
    public static function Create($backer, $id )
    {
        $items = [];
        return new KBGroup($backer, $id, $items);
    }
    public static function ProcessMove(IKBGroupBacker $backer,int $itemId, int $cp=0, int $cg=0, int $cn=0, int $np=0, int $ng=0,int $nn=0) : KBGroupMoveResult
    {
        // null move
        if($np+$ng+$nn == 0)
        {
            if($cg == 0)
            {
                // do nothing
                return new KBGroupMoveResult(noChange: true);
            }
            else
            {
                // remove from $cg
                $currentGroup = self::Load(backer:$backer,id:$cg);
                $items = $currentGroup->Remove(id: $itemId);
                // update cg!
                $currentGroup->Save();
                // item is 0,0,0
                return new KBGroupMoveResult(itemId: $itemId, leftGroup:$cg, affectedItems:$items);
            }
        }
        // resolve target group
        $prevGroupId = self::Find($backer,$np);
        $indexGroupId = $ng;
        $nextGroupId = self::Find($backer,$nn);
        $anchorIndex = -1;
        $targetGroup = null;
        if($ng>0)
        {
            $indexGroup = self::Load($backer, $indexGroupId);
            $targetGroup = $indexGroup;
            if(!$indexGroup)
            {
                $targetGroup =self::Create($backer,$ng );
            }
            $prvindex = $targetGroup->IndexOf($np);
            // prev item not in group
            // attaching to next means taking its slot
            if($prvindex ==-1)
            {
                $nxtindex = $targetGroup->IndexOf($nn);
                // if not found index is -1 which adds at the end anyway as a default
                    $anchorIndex = $nxtindex;
                
            }
            // prev in group
            else
            {
                $anchorIndex = $prvindex+1;
                if(count($targetGroup->items)==$anchorIndex)
                {
                    $anchorIndex = -1;
                }
            }
        }
        
        else
        {
        
            if($prevGroupId>0)
            {
                $prevGroup = self::Load($backer, $prevGroupId);
                $targetGroup = $prevGroup;
                $anchorIndex = $prevGroup->IndexOf($np)+1;
                if(count($prevGroup->items) == $anchorIndex)
                {
                    $anchorIndex = -2;
                }
            }
            if(!$targetGroup|| $anchorIndex == -1)
            {
                $nextGroup = self::Load($backer, $nextGroupId);
                $targetGroup = $nextGroup;
                if($nextGroup)
                {
                    $anchorIndex = $nextGroup->IndexOf($nn);
                }
            }
            if($anchorIndex == -2)
            {
                $anchorIndex = -1;
            }
        }
        // update target group ID
        $ng = $targetGroup->id;
        // check if group changes
        if($ng != $cg)
        {
            $update = ['id'=>$itemId,'left'=>0,'joined'=>0];
            $update = new KBGroupMoveResult(itemId: $itemId);
            $items = [];
            if($cg!=0)
            {
                // remove from cg
                $currentGroup = self::Load(backer:$backer,id:$cg);
                if($cg)
                {
                    $rmitems = $currentGroup->Remove(id: $itemId);
                    $items =array_merge($items, $rmitems);
// update cg!
                    $update->leftGroup=$currentGroup->id;
                    $currentGroup->Save();
                
                }
            }
            // add to ng
            // this must return item's new positions
            // update ng
            $additems = $targetGroup->Add(id: $itemId, pos: $anchorIndex);
            $items = array_merge($items,$additems);
            
            $targetGroup->Save();
            
            $update->joinedGroup=$targetGroup->id;
            $update->affectedItems = $items;
            $item = $targetGroup->items[$targetGroup->IndexOf($itemId)];
            $update->previousItem=$item['prev'];
            $update->nextItem=$item['next'];
            return $update;
        }
        else
        {
            $update = new KBGroupMoveResult(itemId: $itemId, leftGroup: $targetGroup->id, joinedGroup: $targetGroup->id);
            // move within cg/ng
            // this must return item's new positions
            // update ng/cg
            $currIndex = $targetGroup->IndexOf($itemId);
            if($currIndex ===  $anchorIndex)
            {
                $update = new KBGroupMoveResult(noChange:true);
                return $update;
            }
            $items = $targetGroup->Move($currIndex, $anchorIndex);
            $targetGroup->Save();
            $item = $targetGroup->items[$targetGroup->IndexOf($itemId)];
            $update->previousItem=$item['prev'];
            $update->nextItem=$item['next'];
            $update->affectedItems=$items;
            return $update;
        }
        
    }
    public function Walk()
    {
        $changedItems = [];
        for($i=0;$i<count($this->items);$i++)
        {
            $prev = 0;
            $next = 0;
            $id = $this->items[$i]['id'];
            if($i>0)
            {
                $prev = $this->items[$i-1]['id'];
            }
            if($i<count($this->items)-1)
            {
                $next = $this->items[$i+1]['id'];
            }
            $n = ['id'=>$id,'prev'=>$prev,'next'=>$next];
            $o = $this->items[$i];
            if($n['prev']!=$o['prev'] || $n['next']!=$o['next'])
            {
                $changedItems[]=$n;
            }
            $this->items[$i]=$n;
        }
        return $changedItems;
    }
    
    public function Move($from, $to)
    {
        if($from>=count($this->items) || $from <0 || $to>count($this->items) || $to<-1)
        {
            return null;
        }
        if($from == $to)
        {
            // do nothing but complete contract
            return $this->items[$from];
        }
        if($to > $from)
        {
            $to-=1;
        }
        $itemId = $this->items[$from]['id'];
        $itemlist1 = $this->Remove($itemId);
        $itemlist2 = $this->Add($itemId, $to);
        $items = $itemlist2;
        $updatedIds = [];
        foreach($itemlist2 as $item2)
        {
            $updatedIds[]=$item2['id'];
        }
        foreach($itemlist1 as $item1)
        {
            if(in_array(haystack: $updatedIds,needle: $item1['id']))
            {
                continue;
            }
            $items[]=$item1;
            $updatedIds[]=$item1['id'];
        }
        return $items;
    }
    
    public function Add($id,$pos = -1)
    {
        if($pos >=count($this->items))
        {
            $pos = -1;
        }
        $item = ['id'=>$id,'prev'=>0, 'next'=>0];
        if($pos == -1)
        {
            $newPrev = 0;
            if(count($this->items)!=0)
            {
                $newPrev = $this->items[count($this->items)-1]['id'];
                $this->items[count($this->items)-1]['next'] = $id;
            }
            $item['prev']=$newPrev;
            $this->items[]=$item;
            return $this->Walk();
        }
        array_splice($this->items,$pos,0,[$item]);
        return $this->Walk($id);
    }
    public function IndexOf($itemId)
    {
        for($i=0;$i<count($this->items);$i++)
        {
            if($this->items[$i]['id']==$itemId)
            {
                return $i;
            }
        }
        return -1;
    }
    public function Remove($id)
    {
        $index = $this->IndexOf($id);
        if($index==-1)
        {
            return [];
        }
        array_splice($this->items,$index,1);
        return $this->Walk();
    }
}

class KBPageSequence
{
    public $pages;
    public $id;
    const TABLE_NAME = "kbgroups";
    const FIELDS = ['prev','next'];
    
    public function __construct($pages, $name)
    {
        $this->pages = $pages;
        $this->id = $name;
    }
    
    public static function Load($gid)
    {
        $result = OrderedDBCollection::Load(self::TABLE_NAME,$gid,self::FIELDS);
        if(count($result)<1)
        {
            return null;
        }
        return new KBPageSequence($result,$gid);
    }
    
    public static function Exists($gid)
    {
        return DBHelper::Count(self::TABLE_NAME,'collectionId',['collectionId'=>$gid]);
    }
    
    public static function Create($gid)
    {
        $result = OrderedDBCollection::Load(self::TABLE_NAME,$gid,self::FIELDS);
        return new KBPageSequence($result,$gid);
    }
    
    public static function Find($pageId)
    {
        $findgroup = OrderedDBCollection::FindContaining(self::TABLE_NAME, $pageId);
        if(count($findgroup)>0)
        {
            return self::Load($findgroup[0]);
        }
        return null;
    }
    
    public function CheckNOP($id, $prev, $next)
    {
        _p("checking NOP");
        $idx = $this->IndexOf($id);
        if($idx==-1)
        {
            return false;
        }
        $existing = $this->pages->items[$idx];
        return ($existing['prev']==$prev && $existing['next']==$next);
        
    }
    
    public function IndexOf($id)
    {
        _p("indexOfPage called with $id");
        return $this->pages->IndexOf($id);
    }
    
    public function AddPage($id,$prev,$next)
    {
        _p("addpage called with $id, $prev,$next");
        $updates = [];
        $prevref = null;
        $nextref = null;
        $item=['id'=>$id,'group'=>$this->id,'prev'=>$prev,'next'=>$next];
        // get possible neighbours
        $iprev = $this->IndexOf($item['prev']);
        $inext = $this->IndexOf($item['next']);
        // try prev first
        _p("iprev $iprev inext $inext");
        if($prev && $iprev!=-1)
        {
            _p("adding to prev");
            //var_dump($prev);
            // attach after this
            $prevref = $this->pages->items[$iprev];
            if($prevref['next'])
            {
                // if there's a pre-existing next, shove it up
                $inext = $this->IndexOf($prevref['next']);
                // making sure it exists
                if($inext!=-1)
                {
                    // point the next to this item
                    $this->pages->items[$inext]['prev']=$id;
                    // generate update
                    $update = $this->pages->items[$inext];
                    $nextid=$update['entityId'];
                    $update['id']=$nextid;
                    $update['group']=$this->id;
                    $updates[]=$update;
                    // generate item update
                    $item['next']=$nextid;
                }
                
            }
            // modify previous item to point to this;
            $this->pages->items[$iprev]['next']=$id;
            // generate update
            $update = $this->pages->items[$iprev];
            $previd=$update['entityId'];
            $update['id']=$previd;
            $update['group']=$this->id;
            $updates[]=$update;
            // mutate list
            $this->pages->AddItem($id,$item,$iprev);
            $newIndex = $this->IndexOf($id);
            $newItem = $this->pages->items[$newIndex];
            $newItem['group']=$this->id;
            $newItem['id']=$id;
            $updates[]=$newItem;
            return $updates;
        }
        // try next... next
        if($next && $inext!=-1)
        {
            _p("adding to next");
            //var_dump($next);
            // attach after this
            $nextref = $this->pages->items[$inext];
                _p("found nextref");
                //var_dump($nextref);
            if($nextref['prev'])
            {
                // if there's a pre-existing prev, shove it up
                $iprev = $this->IndexOf($nextref['prev']);
                // making sure it exists
                if($iprev!=-1)
                {
                    // point the prev to this item
                    $this->pages->items[$iprev]['next']=$id;
                    // generate update
                    $update = $this->pages->items[$iprev];
                    $previd=$update['entityId'];
                    $update['id']=$previd;
                    $update['group']=$this->id;
                    $updates[]=$update;
                    // generate item update
                    $item['prev']=$previd;
                }
                
            }
            // modify next item to point to this;
            $this->pages->items[$inext]['prev']=$id;
            // generate update
            $update = $this->pages->items[$inext];
            $nextid=$update['entityId'];
            $update['id']=$nextid;
            $update['group']=$this->id;
            $updates[]=$update;
            // mutate list
            $this->pages->AddItem($id,$item,$inext);
            $newIndex = $this->IndexOf($id);
            $newItem = $this->pages->items[$newIndex];
            $newItem['group']=$this->id;
            $newItem['id']=$id;
            
            $updates[]=$newItem;
            return $updates;
        }
        // both pointers bad
        $item['prev']=null;
        $item['next']=null;
        _p("both pointers bad");
        //var_dump($this->pages);
        $pagecount=count($this->pages);
        if($pagecount>0)
        {
            $iprev = $pagecount-1;            
            // point the prev to this item
            $this->pages->items[$iprev]['next']=$id;
        //var_dump($this->pages);
            // generate update
            $update = $this->pages->items[$iprev];
            $previd=$update['entityId'];
            $update['id']=$previd;
            $update['group']=$this->id;
            $updates[]=$update;
            // generate item update
            $item['prev']=$previd;
        }
        // mutate list
        $this->pages->AddItem($id,$item,-1);
            $newIndex = $this->IndexOf($id);
            $newItem = $this->pages->items[$newIndex];
            $newItem['group']=$this->id;
            $newItem['id']=$id;
        _p("<h1>DONE ADDING</h1>");
        //var_dump($updates);
        $updates[]=$newItem;
        return $updates;
    }
    
    public function RemovePage($id)
    {
        _p("removepage called with $id");
        $updates = [];
        $ord = $this->IndexOf($id);
        // if item not in group, gtfo
        if($ord==-1)
        {
            return [];
        }
        // get item
        $item = $this->pages->items[$ord];
        // get possible neighbours
        $iprev = $this->IndexOf($item['prev']);
        $inext = $this->IndexOf($item['next']);
        $prev = $iprev==-1?null:$this->pages->items[$iprev];
        $next = $inext==-1?null:$this->pages->items[$inext];
        if($prev)
        {
            $this->pages->items[$iprev]['next']=$next && $next['prev']?$next['prev']:null;
            $update=$this->pages->items[$iprev];
            $update['id']=$update['entityId'];
            $update['group']=$this->id;
            $updates[]=$update;
        }
        if($next)
        {
            $this->pages->items[$inext]['prev']=$prev && $prev['next']?$prev['next']:null;
            $update=$this->pages->items[$inext];
            $update['id']=$update['entityId'];
            $update['group']=$this->id;
            $updates[]=$update;
        }
        $this->pages->RemoveItemAt($ord);
        $this->pages->SaveToDB();
        return $updates;
    }
    
    
    public static function ProcessMove($id, $gid, $prev, $next)
    {
        _p("processing move");
        //var_dump([$id,$gid,$prev,$next]);
        $itemsToUpdate = [];
        $groups = [];
        // find if item currently in a group
        $currentGroup = self::Find($id);
        // find if requested group exists
        $newGroup = self::Load($gid);
        if(!$newGroup && $gid)
        {
            $newGroup = self::Create($gid);
        }
        if($currentGroup)
        {
            // check if the request will cause any changes at all
     
            if(($gid || $prev || $next) && $currentGroup->CheckNOP($id,$prev,$next))
            {
                return [];
            }
            // remove from current group
            $itemsToUpdate = array_merge($itemsToUpdate,$currentGroup->RemovePage($id));
            //$groups[]=$id;
        }
        // item currently not in a group
        if(!$newGroup)
        {
            // try prev first
            if($prev)
            {
                $newGroup = self::Find($prev);
            }
            // next?
            if(!$newGroup && $next)
            {
                $newGroup = self::Find($next);
            }
            // no valid group to assign to item, done.
            if(!$newGroup){
                return $itemsToUpdate;
            }
        }
        // from here on, group shouldn't be null
        // insert into group
        $afteradd=$newGroup->AddPage($id, $prev, $next);
        _p("after addpage");
        //var_dump($newGroup);
        //var_dump($afteradd);
        $newGroup->pages->SaveToDB();
            $groups[]=$newGroup->id;
            return $groups;
        $itemsToUpdate = array_merge($itemsToUpdate,$afteradd);
        return $itemsToUpdate;
    }
}

require_once "KBGroup.test.php";