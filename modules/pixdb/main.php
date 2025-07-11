<?php

require_once("Picture.php");

function ModuleAction_pixdb_default($params)
{
    
}

function ModuleAction_pixdb_showall($params)
{
    
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
    $tpl->tokens['ext'] = $pic->extension;
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
            $err = File::$last_error;
            EngineCore::WriteUserError("Failed to upload: $err", "error");
        }
    }
}