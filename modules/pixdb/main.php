<?php

function ModuleFunction_pixdb_list_thumbnail($idlist, $extratext="")
{
    $pics = Picture::GetGallery($idlist);
    $tpl = new TemplateProcessor("pixdb/thumbnailview");
    $tpl->tokens['pictures'] = $pics;
    $tpl->tokens['extra_text'] = $extratext;
    EngineCore::SetPageContent($tpl->process(true));
}

function ModuleAction_pixdb_albums()
{
    $list = EVA::GetAsTable(["title","description","cached_count"],"picture_album");
    $data =[];
    foreach($list as $evaid=>$props)
    {
        $props["id"]=$evaid;
        $props["cached_count"]=$props["cached_count"]===""?"?":$props["cached_count"];
        $data[]=$props;
    }
    $data = array_reverse($data);
    $extratext="";
    $tpl = new TemplateProcessor("pixdb/albumlist");
    $tpl->tokens['albums'] = $data;
    $tpl->tokens['extra_text'] = $extratext;
    EngineCore::SetPageContent($tpl->process(true));
}

function ModuleAction_pixdb_default($params)
{
    ModuleAction_pixdb_albums($params);
}

function ModuleAction_pixdb_showall($params)
{
    $pic_ids = EVA::GetAllOfType("picture");
    // show newest first
    $pic_ids = array_reverse($pic_ids);
    ModuleFunction_pixdb_list_thumbnail($pic_ids);
}

function ModuleAction_pixdb_tag($params)
{
    $searchstring = EngineCore::GET("search", "");
    $notags =(count($params)<1 || $params[0]=="");
    if($notags && !$searchstring)
    {
        $tpl=new TemplateProcessor("pixdb/searchbox");
        EngineCore::AddPageContent($tpl->process(true));
        return;
    }
    else
    {
        $pic_ids = Picture::Find($params, $searchstring);
        $tags = implode(", ", $params);
        $subject ="";
        if($searchstring)
        {
            $subject.=" for \"$searchstring\"";
        }
        if(!$notags)
        {
            $subject.=" in tags <em>$tags</em>";
        }
        ModuleFunction_pixdb_list_thumbnail($pic_ids, "Searching$subject");
    }
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
    $redo_lang=EngineCore::POST("redo_lang","");
    if($redo_lang)
    {
        JobScheduler::Schedule("tesseract",$pic->blob_id);
        EngineCore::GTFO("/pixdb/showpic/$id");
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
    ModuleFunction_pixdb_list_thumbnail($pic_ids, "{$a->title}");
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

require "ingest.php";
function ModuleAction_pixdb_ingest($params)
{
    return Module::SPLIT_ROUTE;
}