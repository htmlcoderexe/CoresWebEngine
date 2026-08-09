<?php
/**
 * Provides a generic ordered collection which saves its state to a database.
 * This is designed to be agnostic of specifics and operates like an interface.
 * This means collection identifiers can be anything a database normally supports,
 * and any additional data is passed on as long as the presence of this data
 * is specified.
 * Items may be inserted and removed at any point of the list,
 * rearranging the remaining items as needed.
 * As collections do not exist as actual entities, there is no need to explicitly
 * create one - calling the ::Load($collectionId) method instantiates a 
 * collection ready to perform operations regardless of any items actually 
 * being present in the database as belonging to it.
 * The collection design does not enforce exclusivity - items may belong to
 * as many collections as there are items, this is up to the user to implement.
 *
 * 
 */
class OrderedDBCollection implements Countable
{
    public $table;
    public $id;
    public $items=[];
    public $extraFields = [];
    private $is_dirty=false;
    
    
    /**
     * Constructs an OrderedDBCollection Object from the necessary bits.
     * @param string $table - database table keeping the collections
     * @param string $id - an identifier for this collection
     * @param string[] $extraFields - any additional fields stored for each entry.
     *                 This must be ordered the same way as the backing database.
     * @param array[] $items - an array of items. Each item is 
     *                an associative array containing 'entityId' key 
     *                plus any additional keys as needed.
     */
    public function __construct($table, $id, $extraFields, $items)
    {
        $this->id = $id;
        $this->table = $table;
        $this->items=$items;
        $this->extraFields = $extraFields;
    }
    public function count() : int
    {
        return count($this->items);
    }
    /**
     * Ensures the list can be automatically saved.
     */
    public function __destruct()
    {
        //$this->SaveToDB();
    }
    
    /**
     * Loads a collection from the database.
     * @param string $table - the backing table.
     * @param string $id - a collection identifier.
     * @param string[] $extrafields - any additional fields stored for each entry.
     *                 This must be ordered the same way as the backing database.
     * @return \OrderedDBCollection An object representing a (possibly empty) OrderedDBCollection.
     */
    public static function Load($table,$id,$extrafields = [])
    {
        $items = self::FetchItems($table,$id,$extrafields);
        $result = new OrderedDBCollection($table, $id, $extrafields, $items);
        return $result;
    }
    /**
     * Loads items belonging to a collection form the database.
     * @param string $table - backing table for the collection.
     * @param string $collectionId - a collection identifier.
     * @param string[] $extrafields - a specification for any additional fields, in
     *                 database order.
     * @return array[] - an array containing items if any found.
     */
    public static function FetchItems($table, $collectionId, $extrafields=[])
    {
        $fields = array_merge(['ordinal','entityId'],$extrafields);
        $q=DBHelper::Select($table, $fields, ['collectionId'=>$collectionId],['ordinal'=>'ASC']);
        //////var_dump($q);
        //die;
        $result = DBHelper::RunTable($q,[$collectionId]);
        $items=[];
        foreach($result as $row)
        {
            $ord=$row['ordinal'];
            unset($row['ordinal']);
            $items[$ord]=$row;
            
        }
        return $items;
    }
    /**
     * Fetches a list of collections containing given item
     * @param string $table - backing table.
     * @param int $entityId - ID of the item to search for.
     * @return string[] - a list of collection identifiers which contain the item.
     */
    public static function FindContaining($table, $entityId)
    {
        $fields = ['collectionId'];
        $q=DBHelper::Select($table, $fields, ['entityId'=>$entityId]);
        return DBHelper::RunList($q,[$entityId]);
    }
    /**
     * Commits the current state of the collection to the database.
     * @return bool true if a write was performed.
     */
    public function SaveToDB()
    {
        if(!$this->is_dirty)
        {
            return false;
        }
        _p("<h2>Saving to DB!!</h2>");
        ////var_dump($this->items);
        ksort($this->items);
        _p("after ksort");
        ////var_dump($this->items);
        
        // safty frist!
        DBHelper::BeginTransaction();
        // erase current entries
        DBHelper::Delete($this->table,['collectionId'=>$this->id]);
        // write updated entries
        // #TODO: this, but as one write
        foreach($this->items as $ord=>$item)
        {
            // always collectionId, ordinal, entityId [, ...]
            $row = array_merge([$this->id,$ord],array_values($item));
            ////var_dump($row);
            DBHelper::Insert($this->table,$row);
        }
        DBHelper::Commit();
        $this->is_dirty=false;
        return true;
    }
    /**
     * Finds the index of a specific item by its ID in this collection
     * @param int $entityId - the ID of the item to find
     * @return int index if found, -1 otherwise.
     */
    public function IndexOf($entityId)
    {
        _p("called IndexOf $entityId on collection");
        //var_dump($this->items);
        foreach($this->items as $ord=>$item)
        {
            if($item['entityId']==$entityId)
            {
                return $ord;
            }
        }
        return -1;
    }
    
    /**
     * Finds the index of a specific item by ID in a specific collection.
     * @param string $table - Backing table
     * @param string $collectionId - the collection identifier
     * @param int $entityId - the ID of the item to find
     * @return int index if found, -1 otherwise.
     */
    public static function DBIndexOf($table, $collectionId, $entityId)
    {
        $fields=['ordinal'];
        $q=DBHelper::Select($table,$fields,['collectionId'=>$collectionId,'entityId'=>$entityId]);
        $ord = DBHelper::RunScalar($q);
        if($ord===false)
        {
            return -1;
        }
        return $ord;
    }
    
    
    /**
     * Removes a specific item from the collection.
     * @param int $entityId - ID of the item to be removed.
     * @param bool $noupdate If false (default), the remaining items will be renumbered to fill the gap.
     * @return bool True if item was in the collection and got removed, false otherwise.
     */
    public function RemoveItem($entityId,$noupdate=false)
    {
        _p("<h2>RemoveItem called with $entityId, $noupdate</h2>");
        $ord = $this->IndexOf($entityId);
        if($ord!=-1)
        {
            return $this->RemoveItemAt($ord,$noupdate);
        }
        return false;
    }
    
    /**
     * Removes a specific item from a given collection.
     * @param string $table - Backing table
     * @param string $collectionId - the collection identifier
     * @param int $entityId - ID of the item to be removed.
     * @param bool $noupdate If false (default), the remaining items will be renumbered to fill the gap.
     * @return bool True if item was in the collection and got removed, false otherwise.
     */
    public static function DBRemoveItem($table, $collectionId, $entityId, $noupdate=false)
    {
        $ord = self::DBIndexOf($table, $collectionId, $entityId);
        if($ord!=-1)
        {
            return self::DBRemoveItemAt($table, $collectionId, $ord, $noupdate);
        }
        return false;
    }
    
    /**
     * Removes a specific item from a specific slot in the collection.
     * @param int $position slot to remove from
     * @param bool $noupdate If false (default), the remaining items will be renumbered to fill the gap.
     */
    
    public function RemoveItemAt($position,$noupdate=false)
    {
        _p("<h2>RemoveItemAt called with $position, $noupdate</h2>");
        // remove from array
        unset($this->items[$position]);
        // renumber array to close the gap unless requested not to
        if(!$noupdate)
        {
            ksort($this->items);
            $this->items = array_values($this->items);
        }
        $this->is_dirty=true;
    }
    
    /**
     * Removes a specific item from a specific slot in a given collection.
     * @param string $table - Backing table
     * @param string $collectionId - the collection identifier
     * @param int $position slot to remove from
     * @param bool $noupdate If false (default), the remaining items will be renumbered to fill the gap.
     */
    
    public static function DBRemoveItemAt($table, $collectionId, $position, $noupdate=false)
    {
        // delete entry from DB
        DBHelper::Delete($table,['collectionId'=>$collectionId, 'ordinal'=>$position]);
        // shift rest of pictures down unless asked not to
        if(!$noupdate)
        {
            self::DBShiftDown($collectionId,$position+1);
        }
    }
    
    /**
     * Adds or overwrites existing slot in the collection with an item.
     * @param int $position Slot to write to.
     * @param int $entityId Item ID to write.
     * @param array[] $extraData an associative array containing the item's data, if any.
     */
    public function SetItemAt($position,$entityId,$extraData=[])
    {
        _p("<h2>SetItemAt called with $position, $entityId, extradata</h2>");
        $newItem = ['entityId'=>$entityId];
        // write fields in the same order as the spec
        foreach($this->extraFields as $field)
        {
            $newItem[$field] = $extraData[$field]??null;
        }
        _p("before set");
        ////var_dump($newItem);
        ////var_dump($this->items);
        
        $this->items[$position]=$newItem;
        _p("before ksort");
        ////var_dump($this->items);
        ksort($this->items);
        _p("after ksort");
        ////var_dump($this->items);
        $this->is_dirty=true;
    }
    
    /**
     * Adds or overwrites existing slot in a given collection with an item.
     * @param string $table - Backing table
     * @param string $collectionId - the collection identifier
     * @param int $position Slot to write to.
     * @param int $entityId Item ID to write.
     * @param array[] $extraData an associative array containing the item's data, if any.
     */
    public static function DBSetItemAt($table, $collectionId, $position,$entityId,$extraData=[])
    {
        $newRow = array_merge([$collectionId,$position,$entityId],array_values($extraData));
        // delete entry from DB
        DBHelper::Delete($table,['collectionId'=>$collectionId, 'ordinal'=>$position]);
        // write new entry
        DBHelper::Insert($table,$newRow);
    }
    
    /**
     * Frees up a specific slot in the collection by shifting all items above it up one slot
     * @param int $start The slot to free up
     */
    public function ShiftUp($start)
    {
        _p("Shiftup called with $start");
        $oldcount=count($this->items);
        for($i=$oldcount-1; $i >=$start;$i--)
        {
            $this->items[$i+1]=$this->items[$i];
        }
        unset($this->items[$start]);
        $this->is_dirty=true;
    }
    
    /**
     * Frees up a specific slot in a given collection by shifting all items above it up one slot.
     * @param string $table - Backing table
     * @param string $collectionId - the collection identifier
     * @param int $start The slot to free up
     */
    public static function DBShiftUp($table, $collectionId, $start)
    {
        $count = DBHelper::Count($table,'ordinal',['collectionId'=>$collectionId]);
        DBHelper::BeginTransaction();
        for($i=$count-1; $i >=$start;$i--)
        {
            DBHelper::Update($table,['ordinal'=>$i+1],['collectionId'=>$collectionId,'ordinal'=>$i]);
        }
        DBHelper::Commit();
    }
    
    
    // note that no equivalent function is provided at the instance level as this is trivially done with array_values()
    
    
    /**
     * Moves items down starting from a specific slot in a given collection by shifting all items above it down one slot.
     * @param string $table - Backing table
     * @param string $collectionId - the collection identifier
     * @param int $start The slot to start moving down from 
     * @note  If this function is used to close up a "hole" after erasing items,
     *        the start point should be above the slot of the item just erased.
     */
    public static function DBShiftDown($table, $collectionId, $start)
    {
        $count = DBHelper::Count($table,'ordinal',['collectionId'=>$collectionId]);
        DBHelper::BeginTransaction();
        for($i=$start; $i <$count;$i++)
        {
            DBHelper::Update($table,['ordinal'=>$i-1],['collectionId'=>$collectionId,'ordinal'=>$i]);
        }
        DBHelper::Commit();
    }
    
    /**
     * Adds an item to the collection, or changes its position if already present.
     * @param int $entityId Item ID to add/move.
     * @param array[] $extraData an associative array containing the item's data, if any.
     *        If not set, and the item is being moved within the group, its data will be reused.
     *        To erase item's data, explicitly set $extraData to an empty array [].
     * @param int $position The slot to add the item to. 
     *        The item currently in the slot, as well as ones after it, 
     *        will be shifted over one slot. 
     *        If -1 or higher than the current item count, 
     *        item will be added to the end.
     * @return void for now.
     */
    public function AddItem($entityId,$extraData=null,$position=-1)
    {
        _p("additem called with  $entityId, extra data, $position");
        $ord=$this->IndexOf($entityId);
        if($position==-1 || $position > count($this->items))
        {
            // if the item isn't in the collection yet, add to end
            if($ord==-1)
            {
                $ic=count($this->items);
                _p("items count: $ic");
                ////var_dump($this->items);
                $this->SetItemAt($ic,$entityId,$extraData);
                ksort($this->items);
                $this->is_dirty=true;
                return;
            }
            // otherwise, do nothing
            else
            {
                return;
            }
        }
        else
        {
            // if old and new position are the same, do nothing
            if($ord == $position)
            {
                return;
            }
            // if item wasn't in the collection yet, shift all items up after the slot and insert
            if($ord == -1)
            {
                $this->ShiftUp($position);                
                $this->SetItemAt($position,$entityId,$extraData);
                $this->is_dirty=true;
                return;
            }
            // if new position is higher than old position, subtract one from new position
            if($position > $ord)
            {
                $position-=1;
            }
            // erase old slot
            // this also shifts items after the slot down by default
            // save the old item data in case it is unchanged
            $oldItem = $this->items[$ord];
            $this->RemoveItemAt($ord);
            // shift up after new slot to free it up
            $this->ShiftUp($position);
            // restore old data if no new data is set
            // strict equality to null is tested because user may wish to set empty data
            if($extraData===null)
            {
                // directly write to the slot
                $this->items[$position] = $oldItem;
            }
            else
            {
                // put the item into the now free slot
                $this->SetItemAt($position,$entityId,$extraData);
            }
            $this->is_dirty=true;
            return;
        }
    }
}

