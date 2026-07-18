<?php




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
                //$this->items[count($this->items)-1]['next'] = $id;
            }
            $item['prev']=$newPrev;
            $this->items[]=$item;
            return $this->Walk();
        }
        array_splice($this->items,$pos,0,[$item]);
        return $this->Walk();
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
