<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of MusicTrack
 *
 * @author admin
 */

Module::DemandProperty("title", "Title", "Title of something.");
Module::DemandProperty("artist", "Artist", "The author or performer of this media file.");
Module::DemandProperty("album", "Album", "A collection released by an artist that this item belongs to.");
Module::DemandProperty("media.duration", "Duration", "Duration of a media file, in seconds.");

class MusicTrack
{
    public $id;
    public $title;
    public $artist;
    public $album;
    public $blobid;
    public $duration;
    
    
    public $eva;
    
    
    public static function Load($id)
    {
        if(!EVA::Exists($id,"musictrack"))
        {
            return null;
        }
        $eva = new EVA($id);
        $result = new MusicTrack();
        $result->eva = $eva;
        $result->title = $eva->attributes['title'] ?? '';
        $result->artist = $eva->attributes['artist'] ?? '';
        $result->album = $eva->attributes['album'] ?? '';
        $result->duration = $eva->attributes['media.duration'];
        $result->blobid = $eva->attributes['blobid'];
        $result->id = $id;
        return $result;
    }
    
    public static function FindDuration($filename, $verbose = false)
    {
        if($verbose)
        {
            echo "[" . $filename . "]";
        }
        if(!file_exists($filename))
        {
            return 0;
        }
        $cmd="ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ";
        $cmd.= escapeshellarg($filename);
        $output = [];
        exec($cmd, $output);
        if($verbose)
        {
            echo $cmd;
            echo $output;
        }
        if($output)
        {
            return intval($output[0]);
        }
        return 0;
    }
    
    public static function GetInfo($filename)
    {
        $cmd = "ffprobe -loglevel error -show_entries format_tags=title,artist,album -of default=noprint_wrappers=1:nokey=0 ";
        $cmd.= escapeshellarg($filename);
        if(!file_exists($filename))
        {
            return [];
        }
        $output = [];
        $info = [
            'title'=>'',
            'artist'=>'',
            'album'=>''
        ];
        exec($cmd, $output);
        if($output)
        {
            $any_tags= false;
            foreach($output as $line)
            {
                $tag_split = explode("=",$line,2);
                if(count($tag_split)!=2)
                {
                    continue;
                }
                $any_tags = true;
                list($tag_type,$tag_value) = $tag_split;
                switch($tag_type)
                {
                    case "TAG:title":
                    {
                        $info['title'] = $tag_value;
                        break;
                    }
                    case "TAG:artist":
                    {
                        $info['artist'] = $tag_value;
                        break;
                    }
                    case "TAG:album":
                    {
                        $info['album'] = $tag_value;
                        break;
                    }
                }
            }
            return $info;
        }
        return $info;
    }
    
    public static function CreateFromFile($blobid)
    {
        $filename = File::GetFilePath($blobid);
        $duration = self::FindDuration($filename);
        if(!$duration)
        {
            return null;
        }
        $tags = self::GetInfo($filename);
        if(!$tags['title'])
        {
            $file = File::Load($blobid);
            $tags['title'] = $file->fullname;
        }
        $eva = EVA::CreateObject("musictrack", EVA::OWNER_CURRENT,$tags);
        $eva->AddAttribute("blobid", $blobid);
        $eva->AddAttribute("media.duration", $duration);
        $eva->Save();
        return self::Load($eva->id);
    }
    
    public static function Ingest($dir)
    {
        echo "ingesting from $dir...<br />";
        $filename = File::SelectNextFile($dir);
        if(!$filename)
        {
            echo "no new files to ingest, quitting<br />";
            return false;
        }
        $filepath = File::GetIngestedFilePath($dir, $filename);
        echo "found &lt;$filename&gt;<br />";
        $duration = self::FindDuration($filepath, true);
        if(!$duration)
        {
            echo "very bad mp3<br />";
            File::RejectFile($dir, $filename);
            return true;
        }
        $file = File::IngestFile($dir, $filename);
        $tags = self::GetInfo(File::GetFilePath($file->blobid));
        echo "getting tags from &lt;{$file->blobid}&gt;...<br />";
        
        if(!$tags['title'])
        {
            $tags['title'] = $filename;
        }
        $eva = EVA::CreateObject("musictrack", EVA::OWNER_CURRENT,$tags);
        $eva->AddAttribute("blobid", $file->blobid);
        $eva->AddAttribute("media.duration", $duration);
        $eva->Save();
        echo "&lt;$filename&gt; added as {$eva->id}.<br />";
        return true;
    }
}
