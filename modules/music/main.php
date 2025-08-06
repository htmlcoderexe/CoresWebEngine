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
        $tpl->tokens['id'] = $id;
        EngineCore::SetPageContent($tpl->process(true));
        return;    
    }
}

function ModuleAction_music_getlibrary($params)
{
    $list = EVA::GetAsTable(["title","artist","blobid","album","media.duration"], "musictrack");
    $output = [];
    foreach($list as $id=>$entry)
    {
        $song = [];
        $song['id'] = $id;
        $song['title'] = $entry['title'];
        $song['length'] = $entry['media.duration'];
        $song['artist'] = $entry['artist'];
        $song['album'] = $entry['album'];
        $song['file'] = $entry['blobid'];
        $output[]=$song;
    }
    EngineCore::RawModeOn();
    HTTPHeaders::ContentType("application/json");
    echo json_encode($output);
    die;    
}
function ModuleAction_music_getsong($params)
{
    $id = array_shift($params);
    $mp3 = MusicTrack::Load($id);
    $song =[];
    if($mp3)
    {
        $song = [
            "title" => $mp3->title,
            "file" => $mp3->blobid,
            "length" => $mp3->duration,
            "artist" => $mp3->artist,
            "album" => $mp3->artist,
            "id" => $mp3->id
        ];
    }
    EngineCore::RawModeOn();
    HTTPHeaders::ContentType("application/json");
    echo json_encode($song);
    die;    
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

function ModuleAction_music_pcommand($params)
{
    $command = array_shift($params);
    $safelist = ['volup','voldown','play','pause'];
    if(in_array($command,$safelist))
    {
        Chip::SendCommand("screen",$command,"");
    }
    EngineCore::FromWhenceYouCame();
    die();
}