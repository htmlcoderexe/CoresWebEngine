<?php


/**
 * Description of HTTPHeaders
 *
 * @author admin
 */
class HTTPHeaders
{
    const Statuses = [
        200 => "OK",
        206 => "Partial Content",
        
        304 => "Not Modified",
        
        404 => "Not Found",
        416 => "Requested Range Not Satisfiable"
    ];
    public static function Status($code)
    {
        if(isset(self::Statuses[$code]))
        {
            header("HTTP/1.1 ".$code." ".self::Statuses[$code]);
        }
        else
        {
            header("HTTP/1.1 ".$code);
        }
    }
    
    public static function ContentType($type)
    {
        header("Content-Type: $type");
    }
    
    public static function EnableBytes()
    {
        header("Accept-Ranges: bytes");
    }
    
    public static function Length($size)
    {
        header("Content-Length: $size");
    }
    
    public static function Range($size,$start=-1,$end=-1)
    {
        if($end==-1)
        {
            header("Content-Range: bytes */$size");
        }
        else
        {
            header("Content-Range: bytes $start-$end/$size");
        }
        
    }
    public static function Location($url)
    {
        header("Location: $url");
    }
    
    public static function CacheDuration($seconds)
    {
        header("Cache-Control: max-age=" . (string) intval($seconds));
    }
    
    /**
     * 
     * @param string $header
     * @return array start, end unless fail
     */
    public static function ParseRangeRequest($header)
    {
        list(,$range) = explode("=",$header);
        // multiple ranges, nope out
        if(strpos($range, ",")!==false)
        {
            return false;
        }
        // empty "from" means 0
        if($range[0]==="-")
        {
            $start = 0;
            $end = substr($range,1);
        }
        // else split by the "-"
        else
        {
            list($start,$end) = explode("-",$range);
            if(!$end)
            {
                $end=-1;
            }
        }
        return [$start,$end];
    }
    
    public static function GetReferer()
    {
        // netbeans pls
        return $_SERVER['HTTP_REFERER'];
    }
}
