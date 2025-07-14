<?php

require_once("Picture.php");

function ModuleFunction_pixbd_list_thumbnail($idlist)
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
    EngineCore::SetPageContent($tpl->process(true));
}

function ModuleAction_pixdb_default($params)
{
    ModuleAction_pixdb_showall($params);
}

function ModuleAction_pixdb_showall($params)
{
    $pic_ids = EVA::GetAllOfType("picture");
    ModuleFunction_pixbd_list_thumbnail($pic_ids);
}

function ModuleAction_pixdb_tag($params)
{
    $tag=$params[0];
    $pic_ids = Tag::Find("picture", $tag);
    ModuleFunction_pixbd_list_thumbnail($pic_ids);
    
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
    EngineCore::SetPageContent($tpl->process(true));
}



function ModuleAction_pixdb_upload($params)
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