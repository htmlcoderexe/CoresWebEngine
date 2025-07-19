<?php

function ModuleAction_music_upload($params)
{
    if(!EngineCore::POST("uploading","") || !isset($_FILES['musicupload']))
    {
        $tpl=new TemplateProcessor("music/upload");
        EngineCore::SetPageContent($tpl->process(true));
        return;
    }
    $farr=$_FILES['musicupload'];
    $tmp=$farr['tmp_name'];
    if(!MusicTrack::FindDuration($tmp))
    {
        EngineCore::WriteUserError("very bad mp3", "error");
        $tpl=new TemplateProcessor("music/upload");
        EngineCore::SetPageContent($tpl->process(true));
        return;
    }
    $f = File::Upload($farr);
    $mp3 = MusicTrack::CreateFromFile($f->blobid);
    EngineCore::GTFO("/music/play/" . $mp3->id);
    
}

function ModuleAction_music_play($params)
{
    $id = array_shift($params);
    $mp3 = MusicTrack::Load($id);
    if($mp3)
    {
        
        $tpl=new TemplateProcessor("music/play");
        $tpl->tokens['files'] = [$mp3];
        EngineCore::SetPageContent($tpl->process(true));
        return;    
    }
}