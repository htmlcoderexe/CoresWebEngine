<?php

$fields=[
    "title"=>"VARCHAR(100)",
    "description"=>"TEXT",
    "thumbnail"=>"VARCHAR(100)",
    "created"=>"INT",
    "cached_count"=>"INT",
    "uid"=>"INT",
    "gid"=>"INT"
];
$fields2=[
    "albumid"=>"INT",
    "picid"=>"INT",
    "ordinal"=>"INT",
    "description"=>"TEXT"
];
define("PIXDB_ALBUMS","pixdb_albums");
define("PIXDB_ALBUM_PICS","pixdb_album_contents");
Module::DemandTable(PIXDB_ALBUMS,$fields);
Module::DemandTable(PIXDB_ALBUM_PICS,$fields2,false);
/**
 * Description of PictureSet
 *
 * @author admin
 */
class PictureSet
{
    public $id;
    public $title;
    public $description;
    public $thumbnail;
    public $created;
    public $pictures;
    public $cached_count;
    private $is_dirty=false;
    
    
    /**
     * Constructs a PictureSet Object from the necessary bits.
     * @param int $id
     * @param string $title
     * @param string $description
     * @param \EVA $eva
     * @param int[] $pictures
     */
    public function __construct($id, $title, $description, $thumbnail, $created,$count)
    {
        $this->id = $id;
        $this->title = $title;
        $this->thumbnail=$thumbnail;
        $this->description = $description;
        $this->created=$created;
        $this->cached_count = $count;
    }
    
    public function __destruct()
    {
        $this->SaveToDB();
    }
    
    /**
     * Loads an album from an ID.
     * @param int $id ID to load.
     * @return \PictureSet|null The album if found, else a null.
     */
    public static function Load($id)
    {
        $fields = ['title','description','thumbnail',
            'created','cached_count',
            'uid','gid'
            ];
        $q=DBHelper::Select(PIXDB_ALBUMS,$fields,['id'=>$id]);
        $result = DBHelper::RunRow($q,[$id]);
        if(!$result)
        {
            return null;
        }
        $album = new PictureSet($id,
                $result['title'],$result['description'],$result['thumbnail'],
                $result['created'],$result['cached_count']);
        
        return $album;
    }
    
    
    /**
     * Create a new album out of a title and description, with an optional list of pictures and thumbnail.
     * @param string $title Album title.
     * @param string $description Album description.
     * @param array $pictures An optional list of Picture object IDs or associative arrays of this form:
     * ['id'=>picture ID, 'description'=> in-album description]. These two types can be mixed, any entry that's only the ID assumes a null description.
     * A null description indicates that no explicit description is set and it may be pulled from the picture's own description if desired.
     * To explicitly set an empty description for the picture in the album, an empty string should be used instead.
     * @param string $thumbnail A BlobID for a thumbnail image for the album. If not supplied, the album will use the thumbnail of the first image, if it exists.
     * If desired to explicitly set a thumbnail without adding any images, pass an empty array [] as the $pictures param.
     * @param int|null $owner Owner of the album, defaults to current user.
     * @return \PictureSet The created album goes here.
     */
    public static function Create($title, $description, $pictures=null,$thumbnail=null,$owner=null)
    {
        $uid=$owner ?? EngineCore::$CurrentUser->userid;
        $now=time();
        // write the basic entry first
        DBHelper::Insert(PIXDB_ALBUMS, [null,$title,$description,"",$now,0,$uid,0]);
        $id = DBHelper::GetLastId();
        // create the object to populate data
        $album = new PictureSet($id,$title,$description,"",$now,0);
        $album->pictures = [];
        // add pictures if any were included
        if($pictures && count($pictures)>0)
        {
            for($i=0;$i<count($pictures);$i++)
            {
                $picture=$pictures[$i];
                // if list of IDs, create entries with empty description
                if(!is_array($picture))
                {
                    $picture=['id'=>$picture,'description'=>null];
                }
                $album->pictures[$i]=$picture;
            }
            // update cached count value
            $album->cached_count = count($pictures);
        }
        // if a thumbnail was provided, set it
        if($thumbnail)
        {
            $album->thumbnail = $thumbnail;
        }
        // else take the first picture's thumbnail, if there are any pictures
        elseif(isset($album->pictures[0]))
        {
            $pic = Picture::Load($album->pictures[0]['id']);
            if($pic)
            {
                $album->thumbnail = $pic->thumbnail_blob_id;
            }
        }
        // write the populated object back to DB and return it
        $album->SaveToDB();
        
        return $album;
    }

    public static function DBGetPictures($albumid)
    {
        $q="SELECT p.id as id, p.blobid as blobid, p.thumbnail as thumb, p.extension as ext, p.width as w, p.height as h, a.description as description "
                . "FROM ".PIXDB_ALBUM_PICS." as a "
                . "INNER JOIN ".PICTURE_TABLE." as p "
                . "ON a.picid = p.id "
                . "WHERE a.albumid = ? "
                . "ORDER BY a.ordinal ASC ";
        $results = DBHelper::RunTable($q,[$albumid]);
        
        return $results;
    }
    
    public function GetPictures()
    {
        if($this->pictures)
        {
            return $this->pictures;
        }
        $fields = ['picid','ordinal','description'];
        $q=DBHelper::Select(PIXDB_ALBUM_PICS,$fields,['albumid'=>$this->id]);
        $result = DBHelper::RunTable($q,[$this->id]);
        $this->pictures=[];
        foreach($result as $row)
        {
            $this->pictures[$row['ordinal']]=['id'=>$row['picid'],'description'=>$row['description']];
            
        }
        $this->cached_count = count($this->pictures);
        return $this->pictures;
    }
    /**
     * Commit the current state of the album to the database.
     * @return bool false if writing failed (for example, due to the picture array not being loaded).
     */
    public function SaveToDB()
    {
        // update cached count and other data
        DBHelper::Update(PIXDB_ALBUMS,['cached_count'=>$this->cached_count,'thumbnail'=>$this->thumbnail,'title'=>$this->title,'description'=>$this->description],['id'=>$this->id]);
        if(!$this->pictures || !$this->is_dirty)
        {
            return false;
        }
        $this->cached_count=count($this->pictures);
        // safty frist!
        DBHelper::BeginTransaction();
        // erase current entries
        DBHelper::Delete(PIXDB_ALBUM_PICS,['albumid'=>$this->id]);
        // write updated entries
        foreach($this->pictures as $ord=>$entry)
        {
            DBHelper::Insert(PIXDB_ALBUM_PICS,[$this->id,$entry['id'],$ord,$entry['description']]);
        }
        DBHelper::Commit();
        $this->is_dirty=false;
        return true;
    }
    /**
     * Find the index of a specific Picture ID in this album
     * @param int $id Picture ID
     * @return int index if found, -1 otherwise.
     */
    public function IndexOf($id)
    {
        $this->GetPictures();
        foreach($this->pictures as $ord=>$entry)
        {
            if($entry['id']==$id)
            {
                return $ord;
            }
        }
        return -1;
    }
    
    /**
     * Find the index of a specific Picture ID in a specific album
     * @param int $albumid Album ID
     * @param int $id Picture ID
     * @return int index if found, -1 otherwise.
     */
    public static function DBIndexOf($albumid, $id)
    {
        $fields=['ordinal'];
        $q=DBHelper::Select(PIXDB_ALBUM_PICS,$fields,['albumid'=>$albumid,'picid'=>$id]);
        $ord = DBHelper::RunScalar($q);
        if($ord===false)
        {
            return -1;
        }
        return $ord;
    }
    
    
    /**
     * Removes a specific Picture from the Album, if the Picture is not in the Album, the operation fails.
     * @param int $id ID of the Picture to be removed.
     * @param bool $noupdate If false (default), the remaining pictures will be renumbered to fill the gap.
     * @return bool True on success, false otherwise.
     */
    public function RemovePicture($id,$noupdate=false)
    {
        $this->GetPictures();
        $ord = $this->IndexOf($id);
        if($ord!=-1)
        {
            return $this->RemovePictureAt($ord,$noupdate);
        }
        return false;
    }
    
    /**
     * Removes a specific Picture from a given Album, if the Picture is not in the Album, the operation fails.
     * @param int $albumid Album ID
     * @param int $id ID of the Picture to be removed.
     * @param bool $noupdate If false (default), the remaining pictures will be renumbered to fill the gap.
     * @return bool True on success, false otherwise.
     */
    public static function DBRemovePicture($albumid,$id,$noupdate=false)
    {
        $ord = self::DBIndexOf($albumid,$id);
        if($ord!=-1)
        {
            return self::DBRemovePictureAt($albumid,$ord,$noupdate);
        }
        return false;
    }
    
    /**
     * Remove a Picture from a specific slot in this Album
     * @param int $pos slot to remove from
     * @param bool $noupdate If false (default), the remaining pictures will be renumbered to fill the gap.
     * @return bool True on success, false otherwise.
     */
    
    public function RemovePictureAt($pos,$noupdate=false)
    {
        $this->GetPictures();
        // remove from array
        unset($this->pictures[$pos]);
        // renumber array to close the gap unless requested not to
        if(!$noupdate)
        {
            $this->pictures = array_values($this->pictures);
        }
        $this->is_dirty=true;
    }
    
    /**
     * Remove a Picture from a specific slot in a given Album
     * @param int $albumid Album ID
     * @param int $pos slot to remove from
     * @param bool $noupdate If false (default), the remaining pictures will be renumbered to fill the gap.
     * @return bool True on success, false otherwise.
     */
    
    public static function DBRemovePictureAt($albumid, $pos,$noupdate=false)
    {
        // delete entry from DB
        DBHelper::Delete(PIXDB_ALBUM_PICS,['albumid'=>$albumid, 'ordinal'=>$pos]);
        // shift rest of pictures down unless asked not to
        if(!$noupdate)
        {
            self::DBShiftDown($albumid,$pos+1);
        }
        return true;
    }
    
    /**
     * Adds or overwrites existing slot in the Album with a Picture and an optional description.
     * @param int $position Slot to write to
     * @param int $id Picture ID to set the slot to
     * @param string|null $description Description to write to the slot, may be left out.
     */
    public function SetPictureAt($position,$id,$description=null)
    {
        $this->GetPictures();
        $this->pictures[$position]=['id'=>$id,'description'=>$description];
        $this->is_dirty=true;
    }
    
    /**
     * Adds or overwrites existing slot in an Album with a Picture and an optional description.
     * @param int $albumid Album ID
     * @param int $position Slot to write to
     * @param int $id Picture ID to set the slot to
     * @param string|null $description Description to write to the slot, may be left out.
     */
    public static function DBSetPictureAt($albumid,$position,$id,$description=null)
    {
        // delete entry from DB
        DBHelper::Delete(PIXDB_ALBUM_PICS,['albumid'=>$albumid, 'ordinal'=>$position]);
        // write new entry
        DBHelper::Insert(PIXDB_ALBUM_PICS,[null,$albumid,$id,$position,$description]);
    }
    
    /**
     * Frees up a specific slot in the Album by shifting all items above it up one slot
     * @param int $start The slot to free up
     */
    public function ShiftUp($start)
    {
        $this->GetPictures();
        $oldcount=count($this->pictures);
        for($i=$oldcount-1; $i >=$start;$i--)
        {
            $this->pictures[$i+1]=$this->pictures[$i];
        }
        unset($this->pictures[$start]);
        $this->is_dirty=true;
    }
    
    /**
     * Frees up a specific slot in a given Album by shifting all items above it up one slot.
     * @param int $albumid Album ID
     * @param int $start The slot to free up
     */
    public static function DBShiftUp($albumid, $start)
    {
        $count = DBHelper::Count(PIXDB_ALBUM_PICS,'ord',['albumid'=>$albumid]);
        DBHelper::BeginTransaction();
        for($i=$count-1; $i >=$start;$i--)
        {
            DBHelper::Update(PIXDB_ALBUM_PICS,['ordinal'=>$i+1],['albumid'=>$albumid,'ordinal'=>$i]);
        }
        DBHelper::Commit();
    }
    
    
    // note that no equivalent function is provided at the instance level as this is trivially done with array_values()
    
    
    /**
     * Moves items down starting from a specific slot in a given Album by shifting all items above it down one slot.
     * @param int $albumid Album ID
     * @param int $start The slot to start moving down from (if this is used to close up a "hole" after erasing items, that should be above the slot of the item just erased).
     */
    
    public static function DBShiftDown($albumid,$start)
    {
        $count = DBHelper::Count(PIXDB_ALBUM_PICS,'ord',['albumid'=>$albumid]);
        DBHelper::BeginTransaction();
        for($i=$start; $i <$count;$i++)
        {
            DBHelper::Update(PIXDB_ALBUM_PICS,['ordinal'=>$i-1],['albumid'=>$albumid,'ordinal'=>$i]);
        }
        DBHelper::Commit();
    }
    
    /**
     * Adds a Picture to the Album, or changes its position if already present.
     * @param int $id Picture ID to add/move.
     * @param string|null $description A description to go along with the Picture - if set to null, and the Picture is already in the Album, the description won't be updated, only changing the position.
     * @param int $position The slot to add the Picture to. The Picture currently in the slot, as well as ones after it, will be shifted over one slot. If -1 or higher than the current Picture count, Picture will be added to the end.
     * @return void for now.
     */
    public function AddPicture($id,$description=null,$position=-1)
    {
        
        $this->GetPictures();
        $posInAlbum=$this->IndexOf($id);
        if($position==-1 || $position > count($this->pictures))
        {
            // if the picture isn't in the album yet, add to end
            if($posInAlbum==-1)
            {
                $this->SetPictureAt(count($this->pictures),$id,$description);
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
            if($posInAlbum == $position)
            {
                return;
            }
            // if picture wasn't in the album yet, shift all pictures up after the slot and insert
            if($posInAlbum == -1)
            {
                $this->ShiftUp($position);                
                $this->SetPictureAt($position,$id,$description);
                $this->is_dirty=true;
                return;
            }
            // if new position is higher than old position, subtract one from new position
            if($position > $posInAlbum)
            {
                $position-=1;
            }
            // erase old slot
            // this also shifts pictures after the slot down by default
            // save the old description in case it is unchanged
            $oldDesc = $this->pictures[$posInAlbum]['description'];
            $this->RemovePictureAt($posInAlbum);
            // shift up after new slot to free it up
            $this->ShiftUp($position);
            // restore old description if no new description is set
            // strict equality to null is tested because user may wish to set the description to empty string
            if($description===null)
            {
                $description = $oldDesc;
            }
            // put the picture into the now free slot
            $this->SetPictureAt($position,$id,$description);
            $this->is_dirty=true;
            return;
        }
    }
    // I'll get to it later
    public function DBAddPicture($id,$description=null,$position=-1)
    {
        $this->GetPictures();
        $posInAlbum=$this->IndexOf($id);
        // if no position is specified or position is out of range
        if($position==-1 || $position > count($this->pictures))
        {
            // if the picture isn't in the album yet, add to end
            if($posInAlbum==-1)
            {
                $this->SetPictureAt(count($this->pictures),$id,$description);
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
            if($posInAlbum == $position)
            {
                return;
            }
            // if picture wasn't in the album yet, shift all pictures up after the slot and insert
            if($posInAlbum == -1)
            {
                $this->ShiftUp($position);                
                $this->SetPictureAt($position,$id,$description);
                return;
            }
            // if new position is higher than old position, subtract one from new position
            if($position > $posInAlbum)
            {
                $position-=1;
            }
            // erase old slot
            // this also shifts pictures after the slot down by default
            // save the old description in case it is unchanged
            $oldDesc = $this->pictures($posInAlbum);
            $this->RemovePictureAt($posInAlbum);
            // shift up after new slot to free it up
            $this->ShiftUp($position);
            // restore old description if no new description is set
            // strict equality to null is tested because user may wish to set the description to empty string
            if($description===null)
            {
                $description = $oldDesc;
            }
            // put the picture into the now free slot
            $this->SetPictureAt($position,$id,$description);
            return;
        }
    }
}
