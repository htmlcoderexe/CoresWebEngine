<?php

require_once("Picture.php");
require_once("PictureSet.php");

function ModuleFunction_pixbd_list_thumbnail($idlist, $extratext="")
{
    $pics = [];
    foreach($idlist as $id)
    {
        $pic = new Picture($id);
        if($pic->id !== 0)
        {
            $pics[]= $pic;
        }
    }
    $tpl = new TemplateProcessor("pixdb/thumbnailview");
    $tpl->tokens['pictures'] = $pics;
    $tpl->tokens['extra_text'] = $extratext;
    EngineCore::SetPageContent($tpl->process(true));
}

function ModuleAction_pixdb_default($params)
{
    ModuleAction_pixdb_showall($params);
}

function ModuleAction_pixdb_showall($params)
{
    $pic_ids = EVA::GetAllOfType("picture");
    // show newest first
    $pic_ids = array_reverse($pic_ids);
    ModuleFunction_pixbd_list_thumbnail($pic_ids);
}

function ModuleAction_pixdb_tag($params)
{
    $tag=$params[0];
    $pic_ids = Tag::Find("picture", $tag);
    ModuleFunction_pixbd_list_thumbnail($pic_ids, "Searching by tag [$tag]");
    
}

function ModuleAction_pixdb_showpic($params)
{
    $id = intval(array_shift($params));
    $pic = new Picture($id);
    if($pic->id == 0)
    {
        // #TODO: 404?
        return;
    }
    $tpl = new TemplateProcessor("pixdb/singlepic");
    $tpl->tokens['blobid'] = $pic->blob_id;
    $tpl->tokens['w'] = $pic->width;
    $tpl->tokens['h'] = $pic->height;
    $tpl->tokens['id'] = $pic->id;
    $tpl->tokens['ext'] = $pic->extension;
    $tags = Tag::GetTags($pic->id);
    $tpl->tokens['tags'] = $tags;
    $tpl->tokens['text'] = $pic->text;
    EngineCore::SetPageContent($tpl->process(true));
}

function ModuleAction_pixdb_viewalbum($params)
{
    $id=intval(array_shift($params));
    $a = PictureSet::Load($id);
    if(!$a)
    {
        die();
    }
    $pic_ids = $a->pictures;
    ModuleFunction_pixbd_list_thumbnail($pic_ids, "{$a->title}");
}



function ModuleAction_pixdb_uploadsingle($params)
{
    if(EngineCore::POST("uploading")!="yes")
    {
        EngineCore::SetPageContent((new TemplateProcessor("pixdb/uploadnew"))->process(true));
    }
    else
    {
        $pic=Picture::FromUpload($_FILES['picupload']);
        if($pic)
        {
            EngineCore::GTFO("/pixdb/showpic/".$pic->id);
        }
        else
        {
            $err = Picture::$last_error;
            EngineCore::WriteUserError("Failed to upload: $err", "error");
            EngineCore::FromWhenceYouCame();
        }
    }
}

function ModuleAction_pixdb_upload($params)
{
    if(EngineCore::POST("uploading")!="yes")
    {
        $max = ini_get("max_file_uploads");
        EngineCore::SetPageContent((new TemplateProcessor("pixdb/uploadbulk,max=$max"))->process(true));
    }
    else
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
                        Tag::Attach($pic->id, $newtag);
                    }
                }
                JobScheduler::Schedule("tesseract",$pic->blob_id);
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
            die();
        }
        if(EngineCore::POST("createalbum",'')=="true")
        {
            $a = PictureSet::Create(EngineCore::POST("albumtitle",''), EngineCore::POST("albumdescription",''), $pic_ids);
            if($a)
            {
                EngineCore::GTFO("/pixdb/viewalbum/".$a->id);
            }
            else
            {
                EngineCore::GTFO("/pixdb/");
            }
            die();
        }
        EngineCore::GTFO("/pixdb/");
        die();
    }
}