<?php

function ModuleAction_pixdb_ingest_view($params)
{
    $id = array_shift($params);
    $page=1;
    $pagesize=100;
    if(count($params)>0)
    {
        $page=$params[0];
    }
    $page-=1;
    $ingest = PictureIngest::Load($id);
    if(!$ingest)
    {
        EngineCore::SetPageContent("fuck");
    }
    $picIDs_all = EVA::GetChildren($id,"picture");
    $picIDs = array_slice($picIDs_all,$page*$pagesize,$pagesize);
    $pics = Picture::GetGallery($picIDs);
    $tpl = new TemplateProcessor("pixdb/managerview");
    if($page>0)
    {
        $tpl->tokens['prev']=$page;
    }
    if(($page+1)*$pagesize<count($picIDs_all))
    {
        $tpl->tokens['next']=$page+2;
    }
    $tpl->tokens['page']=$page+1;
    $tpl->tokens['iid']=$id;
    $tpl->tokens['pictures'] = $pics;
    $tpl->tokens['managermode']=true;
    $tpl->tokens['extra_text'] = "Viewing ".count($picIDs_all)." ingest results for <strong>{$ingest->foldername}</strong>.";
    EngineCore::SetPageContent($tpl->process(true));
    
}
function ModuleAction_pixdb_ingest_create($params)
{
    $id = EngineCore::POST("id", "");
    $foldername=basename(EngineCore::POST("foldername", ""));
    $visibility=EngineCore::POST("visibility", "0");
    $active=EngineCore::POST("active", "0");
    if($id === "")
    {
        $tpl = new TemplateProcessor("pixdb/ingestcreate");
        EngineCore::SetPageContent($tpl->process(true));
        return;
    }
    if($id == -1)
    {
        
        if($foldername==="" || strpbrk($foldername, "\\/?%*:|\"<>") !== FALSE)
        {
            EngineCore::WriteUserError("Invalid directory name [".htmlspecialchars($foldername)."]", "errors");
            EngineCore::GTFO("/pixdb/ingest/create");
            die();
        }
        mkdir(FILESTORE_PATH. DIRECTORY_SEPARATOR . File::INGEST_BASE_DIR . DIRECTORY_SEPARATOR . PictureIngest::PICTURE_INGEST_DIR . DIRECTORY_SEPARATOR . $foldername);
        mkdir(FILESTORE_PATH. DIRECTORY_SEPARATOR . File::INGEST_BASE_DIR . DIRECTORY_SEPARATOR . PictureIngest::PICTURE_INGEST_DIR . DIRECTORY_SEPARATOR . $foldername . DIRECTORY_SEPARATOR . ".failed");
        $ingest = PictureIngest::Create($foldername,$visibility,true);
    }
    else
    {
        $ingest = PictureIngest::Load($id);
        if(!$ingest)
        {
            EngineCore::GTFO("/pixdb/ingest/list");
            die;
        }
        $ingest->visibility_level = $visibility;
        $ingest->active = $active;
        $ingest->Save();
    }
    EngineCore::GTFO("/pixdb/ingest/view/" . $ingest->id);
    die();
}

function ModuleAction_pixdb_ingest_manage($params)
{
    $ingest = PictureIngest::Load($params[0]);
    if(!$ingest)
    {
        EngineCore::GTFO("/pixdb/ingest/list");
        die;
    }
    $data = [
        "visibility"=>$ingest->visibility_level,
        "active"=>$ingest->active,
        "foldername"=>$ingest->foldername,
        "id"=>$ingest->id
    ];
    $tpl = new TemplateProcessor("pixdb/ingestcreate");
    $tpl->tokens=$data;
    EngineCore::SetPageContent($tpl->process(true));
    return;
    
}

function ModuleAction_pixdb_ingest_default($params)
{
    ModuleAction_pixdb_ingest_list($params);
}

function ModuleAction_pixdb_ingest_list($params)
{
    $IDs = EVA::GetAsTable(["ingest.folder","active"],"picture.ingest");
    $list = [];
    foreach($IDs as $id=>$entry)
    {
        $list[]=[
            'id'=>$id,
            'active'=>$entry['active'],
            'folder'=>$entry['ingest.folder']
            ];
    }
    $tpl = new TemplateProcessor("pixdb/ingestlist");
    $tpl->tokens['ingests'] = $list;
    EngineCore::SetPageContent($tpl->process(true));
    
}


function ModuleAction_pixdb_ingest_test($params)
{
    if(EngineCore::POST("uploading")!="yes")
    {
        EngineCore::SetPageContent((new TemplateProcessor("pixdb/uploadnew_1"))->process(true));
    }
    else
    {
        EngineCore::RawModeOn();
        $tmpfile=$_FILES['picupload']['tmp_name'];
        var_dump(exif_read_data($tmpfile, null, true, false));
        die;
    }
}