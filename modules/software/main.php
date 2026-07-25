<?php

function ModuleAction_software_init($params)
{
    Module::DemandTable(SoftwarePackage::TABLE, SoftwarePackage::SCHEMA);
    Module::DemandTable(SoftwarePublisher::TABLE, SoftwarePublisher::SCHEMA);
    Module::DemandTable(SoftwareRelease::TABLE, SoftwareRelease::SCHEMA);
    Module::DemandTable(SoftwareReleaseFile::TABLE, SoftwareReleaseFile::SCHEMA);
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

    $tpl = new TemplateProcessor("software/view");
    $tpl->tokens = (array)$sw;
    EngineCore::SetPageContent($tpl->process(true));
}

function ModuleAction_software_new($params)
{
    $tpl = new TemplateProcessor("software/infoedit");
    $publishers =[['id'=>1, 'name'=>'test'],['id'=>2, 'name'=>'test2']];
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
    //var_dump($_POST);die;
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