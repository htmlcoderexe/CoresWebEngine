<?php

function ModuleAction_software_init($params)
{
    Module::DemandTable(SoftwarePackage::TABLE, SoftwarePackage::SCHEMA);
    Module::DemandTable(SoftwarePublisher::TABLE, SoftwarePublisher::SCHEMA);
    Module::DemandTable(SoftwareRelease::TABLE, SoftwareRelease::SCHEMA);
    Module::DemandTable(SoftwareReleaseFile::TABLE, SoftwareReleaseFile::SCHEMA);
}

function ModuleAction_software_default($params)
{
    ModuleAction_software_all($params);
}

function ModuleAction_software_all($params)
{
    $sw = SoftwarePackage::GetList();
    $tpl = new TemplateProcessor("software/listview");
    $tpl->tokens['sw'] =$sw;
    EngineCore::SetPageContent($tpl->process(true));
    
}

function ModuleFunction_listpublishers()
{
        $tg = SoftwarePublisher::TABLE;
        $tt = SoftwarePackage::TABLE;
        $qc = "SELECT $tg.id as pid, $tg.name as name, COUNT($tt.id) as swcount, $tg.description as description "
                . "FROM $tg LEFT JOIN $tt "
                . "ON $tg.id = $tt.publisher "
                . "GROUP BY $tg.id "
                . "ORDER BY swcount ";
        $groups = DBHelper::RunTable($qc, []);
        return $groups;
}

function ModuleAction_software_publisher($params)
{
    $id = intval($params[0] ?? 0);
    $publisher = SoftwarePublisher::Load($id);
    if($publisher)
    { 
        $sw = SoftwarePackage::GetList(['publisher'=>$id]);
        $tpl = new TemplateProcessor("software/listview");
        $tpl->tokens['sw'] =$sw;
        EngineCore::SetPageContent($tpl->process(true));
    }
    else
    {
        $publishers = ModuleFunction_listpublishers();
        $tpl = new TemplateProcessor("software/publishers");
        $tpl->tokens['publishers'] =$publishers;
        EngineCore::SetPageContent($tpl->process(true));
        
    }
    
}

function ModuleAction_software_view($params)
{
    $id = intval($params[0] ?? 0);
    $rid = intval($params[1] ?? 0);
    $sw = SoftwarePackage::Load($id);
    $release = SoftwareRelease::Load($rid);
    if(!$sw)
    {
        return;
    }
    if($release && $release->software_id == $id)
    {
        $tpl = new TemplateProcessor("software/viewrelease");
        $tpl->tokens= (array)$release;
        $tpl->tokens['software_name']= $sw->title;
        $files = [];
        foreach($release->files as $file)
        {
            $fp = File::Load($file->blobid);
            if(!$fp)
            {
                continue;
            }
            $flat = (array)$fp;
            $flat['comment'] = $file->comment;
            $files[]=$flat;
        }
        $tpl->tokens['files']=$files;
        EngineCore::SetPageContent($tpl->process(true));
        return;
    }
    if(EngineCore::$CurrentUser->HasPermission('super'))
    {
        $addlink = "<a href=\"/software/newrelease/$id\">Add new version</a><br />";
        EngineCore::AddPageContent($addlink);
    }
    $tpl = new TemplateProcessor("software/view");
    $tpl->tokens = (array)$sw;
    $publisher = SoftwarePublisher::Load($sw->publisher);
    if($publisher)
    {
        $tpl->tokens['publisher_name'] = $publisher->name;
        $tpl->tokens['publisher_description'] = $publisher->description;
        $tpl->tokens['publisher_id'] = $publisher->id;
    }
    EngineCore::AddPageContent($tpl->process(true));
}

function ModuleAction_software_new($params)
{
    $tpl = new TemplateProcessor("software/infoedit");
    $publishers = SoftwarePublisher::GetList();
    $tpl->tokens['publishers'] = $publishers;
    EngineCore::SetPageContent($tpl->process(true));
}
function ModuleAction_software_newrelease($params)
{
    $id = intval($params[0]) ?? 0;
    $sw = SoftwarePackage::Load($id);
    if(!$sw)
    {
        
        return;
    }
    $tpl = new TemplateProcessor("software/releaseedit");
    $tpl->tokens['software_id'] = $id;
    $tpl->tokens['software_name'] = $sw->title;
    EngineCore::SetPageContent($tpl->process(true));
}
function ModuleAction_software_save($params)
{
    $id = intval(EngineCore::POST('id',-1));
    $title = EngineCore::POST('title','');
    $icon = EngineCore::POST('icon','');
    $description = EngineCore::POST('description','');
    $type = intval(EngineCore::POST('type',0));
    $category = intval(EngineCore::POST('category',0));
    $publisher = intval(EngineCore::POST('publisher',0));
    $album = PictureSet::Create("Screenshots", "Screenshots for ".$title);
    $screenshot_album = $album->id;
    $uid = EngineCore::$CurrentUser->userid;
    $gid = 0;
    if($publisher == -1)
    {
        $pname = EngineCore::POST('pubname','Unknown');
        $pdesc = EngineCore::POST('pubdesc');
        $p = SoftwarePublisher::Create($pname, $pdesc, '');
        $publisher = $p->id;
    }
    if($id==-1)
    {
        // create new
        $package = SoftwarePackage::Create($title, $description, $icon, $screenshot_album, $category, $publisher, $type, $uid, $gid);
        EngineCore::GTFO('/software/view/'.$package->id);
    }
    else
    {
        // edit existing
    }
}
function ModuleAction_software_saverelease($params)
{
    $id = intval(EngineCore::POST('id',-1));
    $software_id = intval(EngineCore::POST('software_id',-1));
    $version = EngineCore::POST('version','');
    $description = EngineCore::POST('description','');
    $type = intval(EngineCore::POST('type',0));
    $time = time();
    $files_in = $_FILES['release_files']??[];
    $comments_in = EngineCore::Post('file_comments');
    if($id==-1)
    {
        // create new
        $release = SoftwareRelease::Create($software_id, $version, $description, $type, $time);
        if(isset($files_in['name']))
        {
            for($i=0;$i<count($files_in['name']);$i++)
            {
                $file = File::Upload($files_in, $i);
                if($file)
                {
                    $fileattach = SoftwareReleaseFile::Create($release->id, $file->blobid, $comments_in[$i]);

                }
            }
        }
        EngineCore::GTFO('/software/view/'.$software_id);
        
    }
    else
    {
        // edit existing
    }
}