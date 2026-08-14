<?php

namespace Controllers;

use Cores\EngineCore;
use Cores\JobScheduler;
use Models\Pictures\Picture;
use Models\Pictures\PictureSet;
use Models\Tags\Tag;
use Route;
use PostRoute;

/**
 * Description of PixDBController
 *
 * @author admin
 */
class PixDBController
{
    public static function ThumbnailView($idlist, $text = '')
    {
        return [
            'entity_type'=>'pixdb/thumbnailview',
            'pictures'=>Picture::GetGallery($idlist),
            'extra_text'=>$text
            ];
    }
    
    #[Route('pixdb/view')]
    public static function ShowImage($id = 0)
    {
        $id = intval($id);
        $pic = Picture::Load($id);
        if(!$pic)
        {
            return EngineCore::Error(404);
        }
        $entity = get_object_vars($pic);
        $entity['entity_type']='pixdb/pic';
        $tags = Tag::GetTags($pic->id,'picture');
        $entity['tags'] = $tags;
        return $entity;
    }
    
    #[PostRoute('pixdb/retesseract', 'pixdb.manage')]
    public static function RedoTesseractOCR($id = 0)
    {
        $id = intval($id);
        $pic = Picture::Load($id);
        if(!$pic)
        {
            return EngineCore::Error(404);
        }
        EngineCore::GTFO("/pixdb/view/$id");
        return;
        
    }
    
    #[Route('pixdb/tag')]
    public static function TagSearch(...$tags)
    {
        $searchstring = EngineCore::GET("search", "");
        $notags =(count($tags)<1 || $tags[0]=="");
        if($notags && !$searchstring)
        {
            return ['entity_type'=>'pixdb/searchbox'];
        }
        $pic_ids = Picture::Find($tags, $searchstring);
        $tags = implode(", ", $tags);
        $subject ="";
        if($searchstring)
        {
            $subject.=" for \"$searchstring\"";
        }
        if(!$notags)
        {
            $subject.=" in tags <em>$tags</em>";
        }
        return self::ThumbnailView($pic_ids, "Searching$subject");
    }
    
    #[Route('pixdb/album')]
    public static function ViewAlbum($id=0)
    {
        $album = PictureSet::Load($id);
        if(!$album)
        {
            return EngineCore::Error(404,"Album does not exist");
        }
        $pics = $album->GetPictures();
        return self::ThumbnailView($pics, "{$album->title}");
    }
    
    #[Route('pixdb/upload','pixdb.upload')]
    public static function ShowUploadForm()
    {
        $max = ini_get("max_file_uploads");
        return ['entity_type'=>'pixdb/uploadbulk','max'=>$max];
    }
    #[PostRoute('pixdb/upload','pixdb.upload')]
    public static function UploadPics()
    {
        $pic_ids = [];
        for($i=0;$i<count($_FILES['picupload']['name']);$i++)
        {
            $pic=Picture::FromUpload($_FILES['picupload'], $i);
            if($pic)
            {
                if(EngineCore::POST("applytags","")=="true")
                {
                    foreach(EngineCore::POST("new_tags",[]) as $newtag)
                    {
                        Tag::Attach($pic->id, $newtag,'picture');
                    }
                }
                $pic_ids[]= $pic->id;
            }
            else
            {
                $err = Picture::$last_error;
                EngineCore::WriteUserError("Failed to upload: $err", "error");
            }
        }
        if(!$pic_ids) // epic fail
        {
            EngineCore::GTFO("/pixdb/");
        }
        if(EngineCore::POST("createalbum",'')=="true")
        {
            $a = PictureSet::Create(EngineCore::POST("albumtitle",''), EngineCore::POST("albumdescription",''), $pic_ids);
            if($a)
            {
                EngineCore::GTFO("/pixdb/album/".$a->id);
            }
            else
            {
                EngineCore::GTFO("/pixdb/");
            }
        }
        EngineCore::GTFO("/pixdb/");
    }
    
}
