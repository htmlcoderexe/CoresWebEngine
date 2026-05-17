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
            1400=>[
                ['id'=>600,'prev'=>0,'next'=>700],
                ['id'=>700,'prev'=>600,'next'=>2900],
                ['id'=>2900,'prev'=>700,'next'=>0],
            ],
            1000=>[
                ['id'=>4100,'prev'=>0,'next'=>1200],
                ['id'=>1200,'prev'=>4100,'next'=>500],
                ['id'=>500,'prev'=>1200,'next'=>200],
                ['id'=>200,'prev'=>500,'next'=>0],
            ],
        ];
    public const testitems1 = [
            ['id'=>10400, 'prev'=> 0, 'next'=> 19900],
            ['id'=>19900, 'prev'=> 10400, 'next'=> 11100],
            ['id'=>11100, 'prev'=> 19900, 'next'=> 10000],
            ['id'=>10000, 'prev'=> 11100, 'next'=> 0],
    ];
    public static function TestFind()
    {
        
        $backer = new KBGroupTestBacker(self::testdata1);
        $testGroup2 = KBGroup::Find(backer: $backer, id: 700);
        var_dump($testGroup2);
    }
    public static function TestLoad()
    {
        $backer = new KBGroupTestBacker(self::testdata1);
        $testGroup1 = KBGroup::Load(backer:  $backer, id: 1400);
        var_dump($testGroup1);
    }
    
    public static function TestSave1()
    {
        $backer = new KBGroupTestBacker(self::testdata1);
        $testGroup3 = new KBGroup(backer: $backer, items: self::testitems1, id: 20000);
        $testGroup3->Save();
        var_dump($backer);
    }
    
    public static function TestAdd()
    {
        $backer = new KBGroupTestBacker(self::testdata1);
        $testGroup14 = KBGroup::Load(backer:  $backer, id: 1400);
        $item300 = $testGroup14->Add(id: 30000);
        self::expect(['id'=>30000,'prev'=>2900,'next'=>0], $item300);
        $item302 = $testGroup14->Add(id: 30200, pos: 0);
        self::expect(['id'=>30200,'prev'=>0,'next'=>600],$item302);
    }
    public static function TestRemove()
    {
        $backer = new KBGroupTestBacker(self::testdata1);
        $testGroup14 = KBGroup::Load(backer:  $backer, id: 1400);
        $testGroup14->Remove(id: 600);
        $testGroup14->Remove(id: 2900);
        self::expect([['id'=>700,'prev'=>0,'next'=>0]],$testGroup14->items);
    }
    public static function TestMove()
    {
        echo "<h1>Testing move with Mock</h1><hr />";
        $backer = new KBGroupTestBacker(self::testdata1);
        $testGroup14 = KBGroup::Load(backer:  $backer, id: 1400);
        $testGroup14->Move(2,0);
        echo "<h2>Move within group.</h2><hr />";
        self::expect([
                ['id'=>2900,'prev'=>0,'next'=>600],
                ['id'=>600,'prev'=>2900,'next'=>700],
                ['id'=>700,'prev'=>600,'next'=>0],
            ],$testGroup14->items);
        $testGroup14->Save();
        $testGroup10 = KBGroup::Load(backer: $backer, id: 1000);
        echo "<h2>Move 600 from 1400 to 1000, set after 1200</h2><hr />";
        $item6=KBGroup::ProcessMove(backer: $backer, itemId: 600,cp:0,cg:1400,cn:0,np:1200,ng:1000,nn:0);
        ksort($item6);
        self::expect(['id'=>600,'joined'=>1000,'left'=>1400,'next'=>500,'prev'=>1200],$item6);
        echo "<h2>Move 4100 from 1000 to 1400, set before 700</h2><hr />";
        $item41=KBGroup::ProcessMove(backer: $backer, itemId: 4100,cp:0,cg:1000,cn:0,np:0,ng:1400,nn:700);
        ksort($item41);
        self::expect(['id'=>4100,'joined'=>1400,'left'=>1000,'next'=>700,'prev'=>2900],$item41);
        echo "<h2>Move 200000 from nowhere to 205000, which doesn't exist yet</h2><hr />";
        $item2000=KBGroup::ProcessMove(backer: $backer, itemId: 200000, cp:0,cg:0,cn:0,np:0,ng:205000,nn:0);
        ksort($item2000);
        self::expect(['id'=>200000,'joined'=>205000,'left'=>0,'next'=>0,'prev'=>0],$item2000);
        var_dump($backer);
    }
    public static function TestMoveWithDB()
    {
        echo "<h1>Testing move with DB</h1><hr />";
        /// setup
        $backer = new KBGroupDBBacker();
        $testGroup14 = new KBGroup(backer: $backer, items: self::testdata1[1400],id: 1400);
        $testGroup14->Save();
        $testGroup10 = new KBGroup(backer: $backer, items: self::testdata1[1000],id: 1000);
        $testGroup10->Save();
        /// end setup
        $testGroup14->Move(2,0);
        echo "<h2>Move within group.</h2><hr />";
        self::expect([
                ['id'=>2900,'prev'=>0,'next'=>600],
                ['id'=>600,'prev'=>2900,'next'=>700],
                ['id'=>700,'prev'=>600,'next'=>0],
            ],$testGroup14->items);
        $testGroup14->Save();
        echo "<h2>Move 600 from 1400 to 1000, set after 1200</h2><hr />";
        $item6=KBGroup::ProcessMove(backer: $backer, itemId: 600,cp:0,cg:1400,cn:0,np:1200,ng:1000,nn:0);
        ksort($item6);
        self::expect(['id'=>600,'joined'=>1000,'left'=>1400,'next'=>500,'prev'=>1200],$item6);
        echo "<h2>Move 4100 from 1000 to 1400, set before 700</h2><hr />";
        $item41=KBGroup::ProcessMove(backer: $backer, itemId: 4100,cp:0,cg:1000,cn:0,np:0,ng:1400,nn:700);
        ksort($item41);
        self::expect(['id'=>4100,'joined'=>1400,'left'=>1000,'next'=>700,'prev'=>2900],$item41);
        echo "<h2>Move 200000 from nowhere to 205000, which doesn't exist yet</h2><hr />";
        $item2000=KBGroup::ProcessMove(backer: $backer, itemId: 200000, cp:0,cg:0,cn:0,np:0,ng:205000,nn:0);
        ksort($item2000);
        self::expect(['id'=>200000,'joined'=>205000,'left'=>0,'next'=>0,'prev'=>0],$item2000);
        
    }
}

class KBGroupDBBacker implements KBGroupBacker
{
    const TABLE = 'kbgroups';
    public function Find($id)
    {
        $fields = ['collectionId'];
        $q=DBHelper::Select(self::TABLE, $fields, ['entityId'=>$id]);
        $id = DBHelper::RunScalar($q,[$id]);
        if($id===false)
            return 0;
        return intval($id);
    }
    
    public function GetItems($id)
    {
        $fields = ['ordinal','entityId','prev','next'];
        $q=DBHelper::Select(self::TABLE, $fields, ['collectionId'=>$id],['ordinal'=>'ASC']);
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
        DBHelper::Delete(self::TABLE,['collectionId'=>$id]);
        // write updated entries
        // #TODO: this, but as one write
        foreach($items as $ord=>$item)
        {
            // always collectionId, ordinal, entityId [, ...]
            $row = [$id,$ord,$item['id'],$item['prev'],$item['next']];
            ////var_dump($row);
            DBHelper::Insert(self::TABLE,$row);
        }
        DBHelper::Commit();
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
    public static function ProcessMove($backer,$itemId, $cp,$cg,$cn,$np,$ng,$nn)
    {
        // null move
        if($np+$ng+$nn == 0)
        {
            if($cg == 0)
            {
                // do nothing
                return ['id'=>$itemId,'prev'=>0,'next'=>0,'left'=>0,'joined'=>0];
            }
            else
            {
                // remove from $cg
                $currentGroup = self::Load(backer:$backer,id:$cg);
                $currentGroup->Remove(id: $itemId);
                // update cg!
                $currentGroup->Save();
                // item is 0,0,0
                return ['id'=>$itemId,'prev'=>0,'next'=>0,'left'=>$cg,'joined'=>0];
            }
        }
        // resolve target group
        $prevGroup = self::Find($backer,$np);
        $indexGroup = self::Find($backer,$ng);
        $nextGroup = self::Find($backer,$nn);
        $anchorIndex = -1;
        $targetGroup = $prevGroup;
        if($prevGroup)
        {
            $anchorIndex = $prevGroup->IndexOf($np)+1;
            if(count($prevGroup->items) == $anchorIndex)
            {
                $anchorIndex = -2;
            }
        }
        if(!$targetGroup|| $anchorIndex == -1)
        {
            $targetGroup = $nextGroup;            
            if($nextGroup)
            {
                $anchorIndex = $nextGroup->IndexOf($nn);
            }
        }
        if(!$targetGroup || $anchorIndex == -1)
        {
            $targetGroup = $indexGroup;
            if(!$indexGroup)
            {
                $targetGroup =self::Create($backer,$ng );
            }
        }
        if($anchorIndex == -2)
        {
            $anchorIndex = -1;
        }
        // update target group ID
        $ng = $targetGroup->id;
        // check if group changes
        if($ng != $cg)
        {
            $update = ['id'=>$itemId,'left'=>0,'joined'=>0];
            if($cg!=0)
            {
                // remove from cg
                $currentGroup = self::Load(backer:$backer,id:$cg);
                if($cg)
                {
                    $currentGroup->Remove(id: $itemId);
                    // update cg!
                    $update['left']=$currentGroup->id;
                    $currentGroup->Save();
                
                }
            }
            // add to ng
            // this must return item's new positions
            // update ng
            $item = $targetGroup->Add(id: $itemId, pos: $anchorIndex);
            $update['joined']=$targetGroup->id;
            $update['prev']=$item['prev'];
            $update['next']=$item['next'];
            
            $targetGroup->Save();
            return $update;
        }
        else
        {
            $update = ['id'=>$itemId,'left'=>$targetGroup->id,'joined'=>$targetGroup->id];
            // move within cg/ng
            // this must return item's new positions
            // update ng/cg
            $currIndex = $targetGroup->IndexOf($itemId);
            $item = $targetGroup->Move($currIndex, $anchorIndex);
            $update['prev']=$item['prev'];
            $update['next']=$item['next'];
            $targetGroup->Save();
            return $update;
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
            $newPrev = 0;
            if(count($this->items)!=0)
            {
                $newPrev = $this->items[count($this->items)-1]['id'];
                $this->items[count($this->items)-1]['next'] = $id;
            }
            $item['prev']=$newPrev;
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