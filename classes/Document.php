<?php

Module::DemandProperty("document.sensitivity", "Sensitivity", "Confidentiality level of a given piece of information");
/**
 * Description of Document
 *
 * @author admin
 */
class Document
{
    public $id;
    public $title;
    public $fileIDs;
    public $sensitivity;
    public $description;
    public $tags;
    
    public const SENSITIVITY_PUBLIC = 0;
    public const SENSITIVITY_GROUP = 1;
    public const SENSITIVITY_PRIVATE = 2;
    public const SENSITIVITY_SECRET = 3;
    
    function __construct($id)
    {
        $e=new EVA($id);
        $this->title = $e->attributes['title'];
        $this->fileIDs =$e->attributes['blobid'];
        $this->sensitivity = $e->attributes['document.sensitivity'];
        $this->description = $e->attributes['description'];
        $this->tags = Tag::GetTags($id);
        $this->id = $id;
    }
    
    public static function Create($title,$fileIDs,$description="",$owner=EVA::OWNER_NOBODY,$sensitivity=self::SENSITIVITY_PUBLIC)
    {
        $e = EVA::CreateObject("document", $owner);
        foreach($fileIDs as $fileID)
        {
            $e->AddAttribute("blobid", $fileID);
        }
        $e->AddAttribute("document.sensitivity",$sensitivity);
        $e->AddAttribute("description",$description);
        $e->AddAttribute("title",$title);
        $e->Save();
        return new Document($e->id);
    }
    
}
