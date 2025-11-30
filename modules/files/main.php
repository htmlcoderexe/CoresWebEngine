<?php
function ModuleAction_files_stream($params)
{
    $fid=$params[0];
    
    if(isset($_SERVER['HTTP_RANGE']))
    {
        $parsed_range = HTTPHeaders::ParseRangeRequest($_SERVER['HTTP_RANGE']);
        if(!$parsed_range)
        {
            // bad range
            Logger::log("Bad range: ".$_SERVER['HTTP_RANGE']);
            HTTPHeaders::Status(416);
            File::ServeByBlobID($fid);
        }
        else
        {
            Logger::log("Good range: ".$_SERVER['HTTP_RANGE']);
            list($start,$end)=$parsed_range;
            File::ServeByBlobID($fid,$start,$end);
        }
    }
    else
    {
        File::ServeByBlobID($fid);
    }
    
    
}

function ModuleAction_files_migrate($params)
{
    EngineCore::$DEBUG=true;
    DBHelper::$DEBUG=true;
    EngineCOre::RawModeOn();
    $files = EVA::GetAllOfType('file');
    //$files=[];
    $i=0;
    //var_dump($files);
    echo "found ".count($files)." to migrate <br />";
    while($i<count($files))
    {
        $batch=[];
        for($j=0;$j<50;$j++)
        {
            
            if(($j+$i)>=count($files))
            {
                break;
            }
            $batch[]=$files[$j+$i];
        }
        //var_dump($batch);
        $i+=$j;
        echo "batch of " . count($batch) ." to process<br />";
        $table = EVA::GetAsTable(['filename','blobid','hash','file.extension','mimetype','filesize','timestamp'],'file',$batch);
        foreach($table as $id=>$fileinfo)
        {
            DBHelper::Insert(Files_FAT,[
                null, $fileinfo['blobid'],$fileinfo['timestamp'],$fileinfo['mimetype'],
                $fileinfo['filesize'],$fileinfo['filename'],$fileinfo['file.extension'],$fileinfo['hash']
            ]);
        }
        flush();
    }
    
}