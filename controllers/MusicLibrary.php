<?php
namespace Controllers;

use \Route;
use Models\MusicTrack;
use Cores\EngineCore;

/**
 * Description of MusicLibrary
 *
 * @author admin
 */
class MusicLibrary
{
    #[Route('music/getlibrary')]
    public static function GetMusicLibrary()
    {
        $tracks = MusicTrack::GetList();
        $entity = ['entity_type'=>'music/playlist',
            'tracks' => $tracks
        ];
        return $entity;
    }
    #[Route('music/player')]
    public static function ShowPlayer($playlist = '')
    {
        $entity = ['entity_type'=>'music/player'];
        
        return $entity;
    }
    #[Route('music/track')]
    public static function GetTrack($trackid = 0)
    {
        
        $mp3 = MusicTrack::Load($trackid);
        if($mp3)
        {
            $entity = (array)$mp3;
            $entity['entity_type']='music/track';
            return $entity;
        }
        return \Cores\EngineCore::Error(404, "The specified music track was not found.");
    }
    #[Route('music/toscreen','playercontrol')]
    public static function SendTrackToScreen($trackid = 0)
    {
        $mp3 = MusicTrack::Load($trackid);
        if($mp3)
        {
            Chip::SendCommand("screen","playsong",$mp3->blobid);
            EngineCore::FromWhenceYouCame();
            die;
        }
        return EngineCore::Error(404);
    }
    #[Route('music/pcommand','playercontrol')]
    public static function PlayerCommand($command = '')
    {
        $safelist = ['volup','voldown','play','pause'];
        if(in_array($command,$safelist))
        {
            Chip::SendCommand("screen",$command,"");
            EngineCore::FromWhenceYouCame();
            die();
        }
        return EngineCore::Error(403);
    }
    #[Route('music/upload','musicupload')]
    public static function UploadMP3()
    {
        $sub = EngineCore::GetSubmission([]);
        if(!$sub || !isset($_FILES['musicupload']))
        {
            return ['entity_type'=>'music/upload'];
        }
        $farr=$_FILES['musicupload'];
        $tmp=$farr['tmp_name'];
        if(!MusicTrack::FindDuration($tmp))
        {
            EngineCore::WriteUserError("very bad mp3", "error");
            return ['entity_type'=>'music/upload'];
        }
        $f = File::Upload($farr);
        $mp3 = MusicTrack::CreateFromFile($f->blobid);
        EngineCore::GTFO("/music/play/" . $mp3->id);
    }
}
