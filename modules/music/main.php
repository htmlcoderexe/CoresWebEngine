<?php

function ModuleAction_music_default($params)
{
    ModuleAction_music_all($params);
}

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

function ModuleAction_music_all($params)
{
    $filelist = EVA::GetAllOfType("musictrack");
    $tracks =[];
    foreach($filelist as $id)
    {
        $track = MusicTrack::Load($id);
        if($track)
        {
            $tracks[]=$track;
        }
    }
    $tpl=new TemplateProcessor("music/listfiles");
    $tpl->tokens['tracks']= $tracks;
    EngineCore::SetPageContent($tpl->process(true));
    return;    
    
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

function ModuleAction_music_toscreen($params)
{
    $id = array_shift($params);
    $mp3 = MusicTrack::Load($id);
    if($mp3)
    {
        Chip::SendCommand("screen","playsong",$mp3->blobid);
        EngineCore::FromWhenceYouCame();
        die();
    }
}