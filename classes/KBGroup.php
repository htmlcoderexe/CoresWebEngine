<?php

interface KBGroupBacker
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
    public function Find($id);
}

class KBGroupTestBacker implements KBGroupBacker
{
    public $groups;
    public function __construct($groups = [])
    {
        $this->groups = $groups;
    }
    public function GetItems($id)
    {
        if(array_key_exists($id,$this->groups))
        {
            return $this->groups[$id];
        }
        return [];
    }
    public function Find($id)
    {
        $ids=array_keys($this->groups);
        foreach($ids as $groupId)
        {
            foreach($this->groups[$groupId] as $item)
            {
                if($item['id'] == $id)
                {
                    return $groupId;
                }
            }
        }
        return 0;
    }
    public function SetItems($id,$items)
    {
        $this->groups[$id] = $items;
    }
}

class KBGroupTest
{
    public static function expect($expect, $value)
    {
        $a= json_encode($expect);
        $b= json_encode($value);
        $pass = $a===$b;
        
        echo("<h1> EXPECTED</h1><hr />");
        var_dump($expect);
        echo("<h1 style=\"color:".($pass?'green':'red')."\"> GOT</h1><hr />");
        var_dump($value);
        echo("<hr />");
        return $pass;
    }
    public const testdata1 = [
            14=>[
                ['id'=>6,'prev'=>0,'next'=>7],
                ['id'=>7,'prev'=>6,'next'=>29],
                ['id'=>29,'prev'=>7,'next'=>0],
            ],
            10=>[
                ['id'=>41,'prev'=>0,'next'=>12],
                ['id'=>12,'prev'=>41,'next'=>5],
                ['id'=>5,'prev'=>12,'next'=>2],
                ['id'=>2,'prev'=>5,'next'=>0],
            ],
        ];
    public const testitems1 = [
            ['id'=>104, 'prev'=> 0, 'next'=> 199],
            ['id'=>199, 'prev'=> 104, 'next'=> 111],
            ['id'=>111, 'prev'=> 199, 'next'=> 100],
            ['id'=>100, 'prev'=> 111, 'next'=> 0],
    ];
    public static function TestFind()
    {
        
        $backer = new KBGroupTestBacker(self::testdata1);
        $testGroup2 = KBGroup::Find(backer: $backer, id: 7);
        var_dump($testGroup2);
    }
    public static function TestLoad()
    {
        $backer = new KBGroupTestBacker(self::testdata1);
        $testGroup1 = KBGroup::Load(backer:  $backer, id: 14);
        var_dump($testGroup1);
    }
    
    public static function TestSave1()
    {
        $backer = new KBGroupTestBacker(self::testdata1);
        $testGroup3 = new KBGroup(backer: $backer, items: self::testitems1, id: 200);
        $testGroup3->Save();
        var_dump($backer);
    }
    
    public static function TestAdd()
    {
        $backer = new KBGroupTestBacker(self::testdata1);
        $testGroup14 = KBGroup::Load(backer:  $backer, id: 14);
        $item300 = $testGroup14->Add(id: 300);
        self::expect(['id'=>300,'prev'=>29,'next'=>0], $item300);
        $item302 = $testGroup14->Add(id: 302, pos: 0);
        self::expect(['id'=>302,'prev'=>0,'next'=>6],$item302);
    }
    public static function TestRemove()
    {
        $backer = new KBGroupTestBacker(self::testdata1);
        $testGroup14 = KBGroup::Load(backer:  $backer, id: 14);
        $testGroup14->Remove(id: 6);
        $testGroup14->Remove(id: 29);
        self::expect([['id'=>7,'prev'=>0,'next'=>0]],$testGroup14->items);
    }
    public static function TestMove()
    {
        $backer = new KBGroupTestBacker(self::testdata1);
        $testGroup14 = KBGroup::Load(backer:  $backer, id: 14);
        $testGroup14->Move(2,0);
        self::expect([
                ['id'=>29,'prev'=>0,'next'=>6],
                ['id'=>6,'prev'=>29,'next'=>7],
                ['id'=>7,'prev'=>6,'next'=>0],
            ],$testGroup14->items);
        $testGroup10 = KBGroup::Load(backer: $backer, id: 10);
    }
}

class KBGroupDBBacker
{
    public function Find($id)
    {
        // db stuff
    }
}

class KBGroup
{
    public $items;
    public $id;
    public KBGroupBacker $backer;
    public function __construct(KBGroupBacker $backer, $id,$items)
    {
        $this->id=$id;
        $this->items = $items;
        $this->backer = $backer;
    }
    public static function Load(KBGroupBacker $backer, $id)
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
    public static function Find(KBGroupBacker $backer, $id)
    {
        $gid = $backer->Find($id);
        if($gid!=0)
        {
            return self::Load($backer, $gid);
        }
    }
    
    public static function Create($backer, $id )
    {
        $items = [];
        return new KBGroup($backer, $id, $items);
    }
    public static function ProcessMove($backer, $cp,$cg,$cn,$np,$ng,$nn)
    {
        // null move
        if($np+$ng+$nn == 0)
        {
            if($cg == 0)
            {
                // do nothing
            }
            else
            {
                // remove from $cg
                // update $cg
                // item is 0,0,0
            }
        }
        // resolve target group
        $prevGroup = self::Find($backer,$np);
        $indexGroup = self::Find($backer,$ng);
        $nextGroup = self::Find($backer,$nn);
        $targetGroup = $prevGroup;
        if($targetGroup)
        {
            $targetGroup = $nextGroup;
        }
        if($targetGroup)
        {
            $targetGroup = $indexGroup ?? self::Create($backer,$ng );
        }
        // update target group ID
        $ng = $targetGroup->id;
        // check if group changes
        if($ng != $cg)
        {
            if($cg!=0)
            {
                // remove from cg
                // update cg!
            }
            // add to ng
            // this must return item's new positions
            // update ng
        }
        else
        {
            // move within cg/ng
            // this must return item's new positions
            // update ng/cg
        }
        
    }
    public function Walk($itemId=-1)
    {
        $item = null;
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
            $updatedItem = ['id'=>$id,'prev'=>$prev,'next'=>$next];
            $this->items[$i]=$updatedItem;
            if($id == $itemId)
            {
                $item = $updatedItem;
            }
        }
        return $item;
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
        $this->Remove($itemId);
        $item = $this->Add($itemId, $to);
        return $item;
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
            $item['prev']=$this->items[count($this->items)-1]['id'];
            $this->items[count($this->items)-1]['next'] = $id;
            $this->items[]=$item;
            return $item;
        }
        array_splice($this->items,$pos,0,[$item]);
        $item = $this->Walk($id);
        return $item;
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
            return false;
        }
        array_splice($this->items,$index,1);
        $this->Walk();
        return true;
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