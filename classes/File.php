<?php
define("FILESTORE_PATH","filestore");

        Module::DemandProperty("filename","Filename","Name of a file");
        Module::DemandProperty("mimetype","MIME Type","Indicates the media type of associated content.");
        Module::DemandProperty("file.extension","File Extension","The last part of a file name used by some operating systems to quickly determine the type of the file without examining its contents.");
        Module::DemandProperty("filesize","File size");
        Module::DemandProperty("blobid","BLOB ID","Identifies the name given to the BLOB file");
        Module::DemandProperty("timestamp","Timestamp","A Unix timestamp to uniquiely identify a point in time.");
class File
{
    public $id;
    public $blobid;
    public $fname;
    public $fullname;
    public $filext;
    public $timestamp;
    public $comment;
    public $prev;
    public $type;
    public $filesize;
    private $evaobj;
    
    public static $last_error;
    
    public const MIME_TYPES =[
        "pdf"=>"application/pdf",
        "jpg"=>"image/jpeg",
        "jpeg"=>"image/jpeg",
        "png"=>"image/png",
        "gif"=>"image/gif",
        "mp3"=>"audio/mpeg",
        "mp4"=>"video/mp4"
        ];
    
    public const BUFFER_SIZE = 8192;
    public const BLOBID_LENGTH = 16;
    
    public function __construct($fileid)
    {
        $id = (int) $fileid;
        $f = new EVA($id);
        $this->fname = $f->attributes['filename'];
        $this->filext=$f->attributes['file.extension'];
        $this->timestamp=$f->attributes['timestamp'];
        $this->type=$f->attributes['mimetype'];
        $this->filesize=$f->attributes['filesize'];
        $this->blobid=$f->attributes['blobid'];
        $this->fullname=$this->fname.".".$this->filext;
        $this->evaobj = $f;
    }
    
    public function GetFileHandle($mode = "w+")
    {
        $handle = fopen(File::GetFilePath($this->blobid), $mode);
        return $handle;
    }
    
    public function UpdateSize($size)
    {
        $this->filesize = $size;
        $this->evaobj->SetSingleAttribute("filesize", $size);
        $this->evaobj->Save();
    }
    
    public static function GetByBlobID($blobid)
    {
        $results=EVA::GetByProperty("blobid", $blobid, "file");
        if($results)
        {
            return new File($results[0]);
        }
    }
    
    public static function GetFileDir($blobname)
    {
        $dir1=$blobname[0].$blobname[1];
        $dir2=$blobname[2].$blobname[3];
        
        $path2=FILESTORE_PATH.DIRECTORY_SEPARATOR.$dir1.DIRECTORY_SEPARATOR.$dir2;
        return $path2;
    }
    public static function GetFilePath($blobname)
    {
        $dir = File::GetFileDir($blobname);
        return $dir .DIRECTORY_SEPARATOR.$blobname;
    }
    
    public static function New($filename)
    {
        $fname = basename($filename);
        $ext = pathinfo($fname)['extension'];
        $mime = array_key_exists($ext,self::MIME_TYPES) ? self::MIME_TYPES[$ext] : "UNKNOWN";
        $blobname = Utility::CreateRandomString(self::BLOBID_LENGTH,Utility::RANDOM_CHR_MIX);
        $path2=self::GetFileDir($blobname);
        $fullname = self::GetFilePath($blobname);
        self::$last_error=$fullname;
        if(is_dir($path2))
        {
            if(file_put_contents($fullname,'') === false)
            {
                self::$last_error="Couldn't write file.";
                return null;
            }
        }
        else
        {
            if(mkdir($path2,0777,true))
            {
                if(file_put_contents($fullname,'') === false)
                {
                    self::$last_error="Couldn't write file.";
                    return null;
                }
            }
            else
            {
                self::$last_error="Couldn't create directory";
                return null;
            }
        }
        $fileobj=EVA::CreateObject("file");
        $fileobj->AddAttribute("filename",pathinfo($filename)['filename']);
        $fileobj->AddAttribute("mimetype",$mime);
        $fileobj->AddAttribute("file.extension",$ext);
        $fileobj->AddAttribute("filesize",0);
        $fileobj->AddAttribute("blobid",$blobname);
        $fileobj->AddAttribute("timestamp",time());
        $fileobj->Save();
        return new File($fileobj->id);
        
    }
    
    /**
     * 
     * @param an array from $_FILE $uploadArray
     * @param int $index - if specified, $uploadArray contains multiple files and this is an index
     * @return \File EVA "file" object, null on failure (check File::$last_error)
     */
    public static function Upload($uploadArray,$index = -1)
    {
        $filename = basename($index == -1 ? $uploadArray['name'] : $uploadArray['name'][$index]);
        $tempname = $index == -1 ? $uploadArray['tmp_name'] : $uploadArray['tmp_name'][$index];
        $filesize = $index == -1 ? $uploadArray['size'] : $uploadArray['size'][$index];
        
        $newfile = File::New($filename);
        if(!$newfile)
        {
            return null;
        }
        
        $fullname = File::GetFilePath($newfile->blobid);
        if(!move_uploaded_file($tempname,$fullname))
        {
            self::$last_error="Couldn't write file.";
            return null;
        }
        
        $newfile->UpdateSize($filesize);
        return $newfile;
    }
    
    public static function ServeByBlobID($blobid,$from=-1,$to=-1)
    {
        // find relevant file
        $fileids=EVA::GetByProperty("blobid", $blobid, "file");
        // 404 if not found
        if(!$fileids)
        {
            HTTPHeaders::Status(404);
            die("file [$blobid] was not found.");
        }
        $filename = self::GetFilePath($blobid);
        // 404 if no file
        if(!file_exists($filename))
        {
            Logger::log("File <".$filename."> was not found",Logger::TYPE_ERROR,"Missing file");
            HTTPHeaders::Status(404);
            die();
        }
        //open the file and get info from the database
        $file=fopen($filename,"r");
        $fileinfo=new File($fileids[0]);
        // set content-type
        HTTPHeaders::ContentType($fileinfo->type);
        // cache for a day
        HTTPHeaders::CacheDuration(86400);
        $size = $fileinfo->filesize;
        // check the actual file's size and log a warning if there's a mismatch
        $realsize= filesize($filename);
        if($size!=$realsize)
        {
            Logger::log("File <$filename> has wrong size in metadata. Recorded size: $size. Real size: $realsize.",Logger::TYPE_WARNING,"Filesize Mismatch");
            $size = $realsize;
        }
        if($from==-1) // whole file requested
        {
            HTTPHeaders::EnableBytes();
            HTTPHeaders::Range($size);
            HTTPHeaders::Length($size);
            if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
            {
                HTTPHeader::Status(304);
                die();
            }
            fpassthru($file);
        }
        else
        {
            // if no end specified, set to last byte of the file
            if($to === -1 || $to >= $size)
            {
                $to = $size-1;
            }
            // partial content
            HTTPHeaders::Status(206);
            HTTPHeaders::EnableBytes();
            HTTPHeaders::Range($size,$from,$to);
            // go to start of requested range
            fseek($file,$from);
            // set chunk size for reading
            $nextread=self::BUFFER_SIZE;
            // keep reading until flag is set or EOF
            $complete = false;
            while(!feof($file) && !$complete)
            {
                // find current position
                $pointer = ftell($file);
                // check if there's less left to read than a full chunk and adjust last round of reading
                if($pointer+$nextread > ($to+1))
                {
                    $nextread=$to - $pointer + 1;
                    // no more reading rounds
                    $complete = true;
                }
                set_time_limit(0);
                echo fread($file,$nextread);
                flush();
            }
        }
        fclose($file);
        die;
    }
    
}

