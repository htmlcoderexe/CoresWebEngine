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