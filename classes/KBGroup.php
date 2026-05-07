<?php

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
            // mutate list
            $this->pages->AddItem($id,$item,$inext);
            // modify previous item to point to this;
            $this->pages->items[$iprev]['next']=$id;
            // generate update
            $update = $this->pages->items[$iprev];
            $previd=$update['entityId'];
            $update['id']=$previd;
            $update['group']=$this->id;
            $updates[]=$update;
            $updates[]=$item;
            return $updates;
        }
        // try next... next
        if($next && $inext!=-1)
        {
            // attach after this
            $nextref = $this->pages->items[$inext];
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
            // mutate list
            $this->pages->AddItem($id,$item,$iprev);
            // modify nextious item to point to this;
            $this->pages->items[$inext]['prev']=$id;
            // generate update
            $update = $this->pages->items[$inext];
            $nextid=$update['entityId'];
            $update['id']=$nextid;
            $update['group']=$this->id;
            $updates[]=$update;
            $updates[]=$item;
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
        _p("<h1>DONE ADDING</h1>");
        //var_dump($updates);
        $updates[]=$item;
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
        
    }
    
    
    public static function ProcessMove($id, $gid, $prev, $next)
    {
        _p("processing move");
        //var_dump([$id,$gid,$prev,$next]);
        $itemsToUpdate = [];
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
        $itemsToUpdate = array_merge($itemsToUpdate,$afteradd);
        return $itemsToUpdate;
    }
}