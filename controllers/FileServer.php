<?php

namespace Controllers;

class FileServer
{
    #[\Route('files/stream')]
    public static function StreamByBlobId($id)
    {
        //echo "ID was $id lol";
        //die;
        if(isset($_SERVER['HTTP_RANGE']))
        {
            $parsed_range = HTTPHeaders::ParseRangeRequest($_SERVER['HTTP_RANGE']);
            if(!$parsed_range)
            {
                // bad range
                Logger::log("Bad range: ".$_SERVER['HTTP_RANGE']);
                HTTPHeaders::Status(416);
                \File::ServeByBlobID($id);
            }
            else
            {
                Logger::log("Good range: ".$_SERVER['HTTP_RANGE']);
                list($start,$end)=$parsed_range;
                \File::ServeByBlobID($id,$start,$end);
            }
        }
        else
        {
            \File::ServeByBlobID($id);
        }
    }
}
