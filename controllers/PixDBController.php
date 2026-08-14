<?php

namespace Controllers;

use Common\DBHelper;
use Cores\EngineCore;
use Models\Pictures\Picture;
use Models\Pictures\PictureSet;
use Models\Tags\Tag;
use PostRoute;
use Route;

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
    
    
    #[Route('pixdb/default')]
    public static function Index()
    {
        EngineCore::GTFO('/pixdb/albums');
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
    
    #[Route('pixdb/albums')]
    public static function ShowAlbums()
    {
        $q=DBHelper::Select(PictureSet::TABLE,["id","title","description","cached_count"],['1'=>'1'],['id'=>'DESC']);
        $data=DBHelper::RunTable($q,[1]);
        $extratext="<a href=\"/pixdb/ingest/list\">Ingests</a><br />";
        return ["entity_type" => "pixdb/albumlist",
            "albums" => $data,
            "extra_text" => $extratext
        ];
    }
    
    public static function RemoveFromIngest($ingest_id, $picture_ids)
    {
        foreach($picture_ids as $id)
        {
            PictureIngestEntry::Delete(ingest_id: $ingest_id, picture_id: $id);
        }
    }
    
    public static function MassAttachTags($tags, $picture_ids)
    {
        foreach($picture_ids as $id)
        {
            foreach($tags as $tag)
            {
                Tag::Attach($id,$tag,'picture');
            }
        }
    }
    public static function MassRemoveTags($tags, $picture_ids)
    {
        foreach($picture_ids as $id)
        {
            foreach($tags as $tag)
            {
                Tag::Remove($id,$tag,'picture');
            }
        }
    }
    
    #[PostRoute('pixdb/processbatch','pixdb.manage')]
    public static function ProcessImageBatchOperation()
    {
        $picids=EngineCore::POST("picids");
        if(!$picids)
        {
            EngineCore::FromWhenceYouCame();
            die;
        }
        $ids=explode(",",$picids);
        $iid=EngineCore::POST("owner");
        $dis = EngineCore::POST("disassociate");
        if($dis && $iid)
        {
            self::RemoveFromIngest($iid, $picids);
        }
        $tadd=EngineCore::POST("tagstoadd");
        if($tadd)
        {
            self::MassAttachTags($tadd, $picids);
        }
        $trem=EngineCore::POST("tagstoremove");
        if($trem)
        {
            self::MassRemoveTags($tadd, $picids);
        }
        $albumid=EngineCore::POST("albumid",0);

        if(EngineCore::POST("doalbum"))
        {
            if($albumid==-1)
            {
                $album = PictureSet::Create(EngineCore::POST("albumname","untitled"),"",$ids);
            }
            else
            {
                $album = PictureSet::Load($albumid);
                if($album)
                {
                    foreach($ids as $id)
                    {
                        $album->AddPicture($id);
                    }
                }
            }
        }

        EngineCore::FromWhenceYouCame();
    }
    
}
