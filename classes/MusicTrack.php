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

$table = [
    "title"=>"VARCHAR(255)",
    "blobid"=>"VARCHAR(100)",
    "duration"=>"INT",
    "artist"=>"VARCHAR(255)",
    "album"=>"VARCHAR(255)",
    "timestamp"=>"INT",
    "uid"=>"INT",
    "gid"=>"INT"
];

Module::DemandTable(MusicTrack::TABLE, $table);

class MusicTrack
{
    public const TABLE = "music_tracks";
    public const FIELDS = [
        'id','title','artist','album','duration','blobid','timestamp'
    ];
    
    public function __construct(
    public int $id,
    public string $title,
    public string $artist,
    public string $album,
    public string $blobid,
    public int $duration){}
    
    
    public static function RowToObject($row)
    {
        $track = new MusicTrack(
                id: $row['id'],
                title: $row['title'],
                blobid: $row['blobid'],
                duration: $row['duration'],
                artist: $row['artist'],
                album: $row['album']
        );
        return $track;
    }
    
    public static function Load(int $id) : MusicTrack | null
    {
        
        $row = DBHelper::GetRowById(self::TABLE, $id, self::FIELDS);
        if(!$row)
        {
            return null;
        }
        $track = self::RowToObject($row);
        return $track;
    }
    
    public static function GetList(array $filters = [])
    {
        
        // currently filters aren't implemented so this just gets every track ever
        
        $sel = DBHelper::Select(self::TABLE, self::FIELDS, []);
        $rows = DBHelper::RunTable($sel, []);
        
        // after getting appropriate results, return list of object
        
        $list = [];
        foreach($rows as $row)
        {
            $track = self::RowToObject($row);
            $list[]=$track;
        }
        return $list;
    }
    
    public static function Create(string $title, string $blobid, int $duration, string $artist = "", string $album = "", int $owner = EVA::OWNER_NOBODY)
    {
        $now = time();
        $row = [null, $title, $blobid, $duration, $artist, $album, $now, $owner, 0];
        DBHelper::Insert(self::TABLE, $row);
        $id = DBHelper::GetLastId();
        $track = new MusicTrack(
                id: $id,
                title: $title, artist: $artist, album: $album,
                blobid: $blobid, duration: $duration
        );
        return $track;
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
        $track = self::Create(
                title: $tags['title'],
                artist: $tags['artist']??"",
                album: $tags['album']??"",
                duration: $duration,
                blobid: $blobid
        );
        return $track;
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
        $track = self::Create(
                title: $tags['title'],
                artist: $tags['artist']??"",
                album: $tags['album']??"",
                duration: $duration,
                blobid: $file->blobid
        );
        echo "&lt;$filename&gt; added as {$track->id}.<br />";
        return true;
    }
}
