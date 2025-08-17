<?php

Module::DemandProperty("ingest.folder", "Source ingest folder", "Folder used for this ingest operation");
Module::DemandProperty("active","Active","Indicates whether this item is active or not.");
Module::DemandProperty("visibility","Public visibility","Indicates the visibility level of this item");

/**
 * Description of PictureIngest
 *
 * @author admin
 */
class PictureIngest
{
    public $id;
    public $active;
    public $foldername;
    public $visibility_level;
    public $eva;
    
    public const VISIBILITY_OWNER=0;
    public const VISIBILITY_GROUP=1;
    public const VISIBILITY_USERS=2;
    public const VISIBILITY_PUBLIC=3;
    public const PICTURE_INGEST_DIR="pictures";
    
    public function __construct($id, $foldername, $active, $visibility_level, $eva)
    {
        $this->id = $id;
        $this->foldername = $foldername;
        $this->active = $active;
        $this->visibility_level = $visibility_level;
        $this->eva = $eva;
    }
    
    public static function Load($EVAId)
    {
        $eva = EVA::Load($EVAId);
        if(!$eva)
        {
            return null;
        }
        return new PictureIngest($eva->id, $eva->attributes['ingest.folder'], $eva->attributes['active'], $eva->attributes['visibility'],$eva);
    }
    
    public static function Create($foldername, $visibility_level, $active=true)
    {
        $props = [
            "ingest.folder"=>$foldername,
            "active"=>$active,
            "visibility"=>$visibility_level
        ];
        $eva = EVA::CreateObject("picture.ingest",EVA::OWNER_CURRENT,$props);
        return self::Load($eva->id);
    }
    
    public function Run()
    {
        User::SetSU(User::GetCurrentUser()->username);
        $result = Picture::Ingest(self::PICTURE_INGEST_DIR . DIRECTORY_SEPARATOR . $this->foldername);
        // regular ingest results, false is STOP, true is CONTINUE
        if($result === true || $result === false)
        {
            return $result;
        }
        // otherwise, a picture was ingested
        $result->eva->AddAttribute("visibility",$this->visibility_level);
        $this->eva->Adopt($result->id);
        User::ClearSU();
        return true;
    }
}
