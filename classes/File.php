<?php
define("FILESTORE_PATH","filestore");
class File
{
    public $id;
    public $fname;
    public $fullname;
    public $filext;
    public $timestamp;
    public $comment;
    public $prev;
    
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

    public function __construct($fileid)
    {
        $id = (int) $fileid;
        $f = new EVA($id);
        $this->fname = $f->attributes['filename'];
        $this->filext=$f->attributes['file.extension'];
        $this->timestamp=$f->attributes['timestamp'];
    }
    /**
     * 
     * @param an array from $_FILE $uploadArray
     * @param int $index - if specified, $uploadArray contains multiple files and this is an index
     * @return int ID of the associated EVA "file" object, -1 on failure (check File::$last_error)
     */
    public static function Upload($uploadArray,$index = -1)
    {
        $filename = basename($index == -1 ? $uploadArray['name'] : $uploadArray['name'][$index]);
        $tempname = $index == -1 ? $uploadArray['tmp_name'] : $uploadArray['tmp_name'][$index];
        $filesize = $index == -1 ? $uploadArray['size'] : $uploadArray['size'][$index];
        $ext=pathinfo($filename)['extension'];
        $mime = array_key_exists($ext,self::MIME_TYPES) ? self::MIME_TYPES[$ext] : "UNKNOWN";
        $blobname = Utility::CreateRandomString(40,Utility::RANDOM_CHR_MIX);
        $dir1=$blobname[0].$blobname[1];
        $dir2=$blobname[2].$blobname[3];
        
        $path2=FILESTORE_PATH.DIRECTORY_SEPARATOR.$dir1.DIRECTORY_SEPARATOR.$dir2;
        $fullname =$path2.DIRECTORY_SEPARATOR.$blobname;
        self::$last_error=$fullname;
        if(is_dir($path2))
        {
            if(!move_uploaded_file($tempname,$fullname))
            {
                self::$last_error="Couldn't write file.";
                return -1;
            }
        }
        else
        {
            if(mkdir($path2,0777,true))
            {
                if(!move_uploaded_file($tempname,$fullname))
                {
                    self::$last_error="Couldn't write file.";
                    return -1;
                }
            }
            else
            {
                self::$last_error="Couldn't create directory";
                return -1;
            }
        }
        $fileobj=EVA::CreateObject("file");
        $fileobj->AddAttribute("filename",pathinfo($filename)['filename']);
        $fileobj->AddAttribute("mimetype",$mime);
        $fileobj->AddAttribute("file.extension",$ext);
        $fileobj->AddAttribute("filesize",$filesize);
        $fileobj->AddAttribute("blobid",$blobname);
        $fileobj->AddAttribute("timestamp",time());
        $fileobj->Save();
        return $fileobj->id;
    }
    
    public function Serve()
    {
        
    }
    
}

