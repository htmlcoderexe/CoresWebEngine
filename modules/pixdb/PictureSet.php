<?php

/**
 * Description of PictureSet
 *
 * @author admin
 */
class PictureSet
{
    public $id;
    public $pictures;
    public $title;
    public $description;
    public $eva;
    
    public function __construct($id)
    {
        $this->id = 0;
        $eva = new EVA($id);
        if($eva->id<1)
        {
            return;
        }
        $this->eva = $eva;
        $this->pictures = EVA::GetChildren($id, "picture");
        $this->title = $eva->attributes['title'];
        $this->description = $eva->attributes['description'];
    }
    public static function Create($title, $description, $pictures)
    {
        $eva = EVA::CreateObject("picture_album");
        $eva->AddAttribute("title", $title);
        $eva->AddAttribute("description", $description);
        foreach($pictures as $pictureID)
        {
            
        }
    }

}
