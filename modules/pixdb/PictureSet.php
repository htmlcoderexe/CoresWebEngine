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
    
    /**
     * Constructs a PictureSet Object from the necessary bits.
     * @param int $id
     * @param string $title
     * @param string $description
     * @param \EVA $eva
     * @param int[] $pictures
     */
    public function __construct($id, $title, $description, $eva, $pictures)
    {
        $this->id = $id;
        $this->title = $title;
        $this->pictures = $pictures;
        $this->description = $description;
        $this->eva = $eva;
    }
    
    /**
     * Loads an album from an ID.
     * @param int $id ID to load.
     * @return \PictureSet|null The album if found, else a null.
     */
    public static function Load($id)
    {
        $eva = new EVA($id);
        if($eva->id<1)
        {
            return;
        }
        $pictures = EVA::GetChildren($id, "picture");
        $title = $eva->attributes['title'];
        $description = $eva->attributes['description'];
        return new PictureSet($id, $title,$description,$eva,$pictures);
    }
    
    /**
     * Creates a new album out of a title, a description, and a list of pictures.
     * @param string $title Album title.
     * @param string $description Album description.
     * @param int[] $pictures List of Picture object IDs to populate.
     * @return \PictureSet The created album goes here.
     */
    public static function Create($title, $description, $pictures)
    {
        $eva = EVA::CreateObject("picture_album");
        $eva->AddAttribute("title", $title);
        $eva->AddAttribute("description", $description);
        foreach($pictures as $pictureID)
        {
            if(EVA::Exists($pictureID, "picture"))
            {
                $eva->Adopt($pictureID);
            }
        }
        $eva->Save();
        return PictureSet::Load($eva->id);
    }

}
