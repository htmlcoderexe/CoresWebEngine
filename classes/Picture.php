<?php

Module::DemandProperty("thumb_blobid", "Thumbnail File ID", "File ID of the thumbnail image.");
Module::DemandProperty("picture.text", "Image text", "Text contained in the image, may be automatically generated.");
Module::DemandProperty("picture.width", "Width", "Image width, in pixels.");
Module::DemandProperty("picture.height", "Height", "Image height, in pixels.");
Module::DemandProperty("picture.takendate", "Taken date", "Date this picture was taken on.");
Module::DemandProperty("picture.takentime", "Taken time", "Time this picture was taken at.");
Module::DemandProperty("filetime","File time", "Date and time when this file was likely created");
Module::DemandProperty("cached_count","Cached count", "Number of items that's cached for display in tables.");
/**
 * Description of Picture
 *
 * @author htmlcoderexe
 */
class Picture
{
    public const MAXIMUM_DIMENSION = 256;
    
    public static $last_error;
    
    public $id;
    public $blob_id;
    public $thumbnail_blob_id;
    public $width;
    public $height;
    public $extension;
    public $text;
    public $title;
    
    public $aspect_ratio;
    public $thumb_width;
    public $thumb_height;
    public $datetaken;
    public $filetime;
    
    public $eva;
    
    public function __construct($id)
    {
        $eva = new EVA($id);
        $this->id = intval($eva->id);
        if($this->id == 0)
        {
            return;
        }
        $this->blob_id = $eva->attributes["blobid"];
        $this->thumbnail_blob_id = $eva->attributes["thumb_blobid"];
        $this->width = $eva->attributes["picture.width"];
        $this->height = $eva->attributes["picture.height"];
        $this->title = $eva->attributes["title"];
        $this->text = $eva->attributes["picture.text"];
        $this->extension = $eva->attributes["file.extension"];
        // calculated properties
        
        $this->aspect_ratio = $this->width / $this->height;
        
        if(isset($eva->attributes['picture.takendate']))
        {
            $this->datetaken = DateTimeImmutable::createFromFormat("YmdHis", $eva->attributes['picture.takendate'].$eva->attributes['picture.takentime']);
        }
        if(isset($eva->attributes['filetime']))
        {
            $this->filetime =$eva->attributes['filetime'];
        }
        // here, the scale is kept even if it enlarges the image, for display purposes
        
        $scale = self::ScaleThumbnail($this->width, $this->height);
        $this->thumb_width = $this->width * $scale;
        $this->thumb_height = $this->height * $scale;
        
        // keep a reference to the EVA object
        
        $this->eva = $eva;
        
    }
    
    /**
     * Calculate the scale factor that would fit the given image dimensions into a
     * MAXIMUM_DIMENSION-sided square
     * @param int $w
     * @param int $h
     * @return float Scale factor
     */
    public static function ScaleThumbnail($w, $h)
    {
        
        $limiter = max($w, $h);
        return self::MAXIMUM_DIMENSION / $limiter;
    }
    
    public static function Create($blob, $thumb, $w, $h, $title, $text, $ext,$filedate,$totaldate)
    {
        $eva = EVA::CreateObject("picture");
        $eva->AddAttribute("blobid", $blob);
        $eva->AddAttribute("thumb_blobid", $thumb);
        $eva->AddAttribute("picture.width",$w);
        $eva->AddAttribute("picture.height",$h);
        $eva->AddAttribute("title",$title);
        $eva->AddAttribute("picture.text",$text);
        $eva->AddAttribute("file.extension",$ext);
        $eva->AddAttribute("picture.takendate",$totaldate->format("Ymd"));
        $eva->AddAttribute("picture.takentime",$totaldate->format("His"));
        $eva->AddAttribute("filetime",$filedate->getTimeStamp());
        $eva->Save();
        return new Picture($eva->id);
    }
    
    public static function GetImageType($filename)
    {
         $types = ['png','gif','jpeg'];
        // reset the extension
        $ext ="";
        // try whatever formats are left
        foreach($types as $imageformat)
        {
            $imagefx = "imagecreatefrom" . $imageformat;
            $image = @$imagefx($filename);
            // if a valid image was obtained, note the extension/format and leave the loop
            if($image)
            {
                $ext = $imageformat;
                break;
            }
        }
        $type=exif_imagetype($filename);
        if($type === IMAGETYPE_WEBP)
        {
            //$image = imagecreatefromwebp($filename);
            $ext = "webp";
        }
        // if no valid image was found, give up
        if($ext == "")
        {
            self::$last_error = "Invalid image file";
            return null;
        }
        return $ext;
    }
    
    public static function FromFile($blobid)
    {
        $past0 = (new DateTimeImmutable())->SetISODate(1600,1);
        // this will keep "to be attempted" types for later
        $sourcefile = File::GetFilePath($blobid);
        $fobj = File::GetByBlobID($blobid);
        $fname = $fobj->fname;
        $ext = self::GetImageType($sourcefile);
        $imagefx = "imagecreatefrom".$ext;
        $image = @$imagefx($sourcefile);
        // get earliest file-related dates
        $filestat = stat($sourcefile);
        $sourcetimes = [$filestat['atime'],$filestat['mtime'],$filestat['ctime']];
        $candidates = [];
        // exclude zeroes though
        foreach($sourcetimes as $candidate)
        {
            if($candidate >0)
            {
                $candidates[]=$candidate;
            }
        }
        // make a date object out of it
        $filedate = (new DateTimeImmutable())->setTimeStamp(min($candidates));
        
        $totaldate = $filedate;
        // fish for dates in EXIF
        $metadate = $past0;
        if($ext == "jpeg")
        {
            $exif = [];
            $exif = @exif_read_data($sourcefile, null, true, false);
            // the standard tag is this
            if(isset($exif['EXIF']) && isset($exif['EXIF']['DateTimeOriginal']))
            {
                $takendate = $exif['EXIF']['DateTimeOriginal'];
                $metadate = DateTimeImmutable::createFromFormat("Y:m:d H:i:s",$takendate);
            }
            // not sure if this fallback is meaningful
            elseif(isset($exif['IDF0']) && isset($exif['IDF0']['DateTime']))
            {
                $takendate = $exif['IDF0']['DateTime'];
                $metadate = DateTimeImmutable::createFromFormat("Y:m:d H:i:s",$takendate);
            }
            // if exif disappoints, "zero" the date
            else
            {
                // somewhere in the past ok
            }
        }
        // set the cached "real" date to exif date if it exists and lower than the file date
        if($metadate != $past0)
        {
            $totaldate = $metadate < $filedate ? $metadate : $filedate;
            
        }
        
        // get sizes
        $w = imagesx($image);
        $h = imagesy($image);
        
        // not sure if ever happens but definitely fail in this case
        if($w == 0 || $h == 0)
        {
            self::$last_error = "Invalid image dimensions";
            return null;
        }
        
        
        // fit the image inside a MAXIMUM_DIMENSION square
        
        $scale_factor =  self::ScaleThumbnail($w, $h);
        
        // no thumbnail is created if the image is smaller than the square
        
        if($scale_factor > 1)
        {
            // set thumbnail file to the same file
            $new_pic_thumb_blob = $blobid;
        }
        
        // otherwise, generate a thumbnail, store it as a File and return its blobid
        
        else
        {
            $tw = intval($w*$scale_factor);
            $th = intval($h*$scale_factor);
            $thumb = imagecreatetruecolor($tw,$th);
            imagecopyresampled($thumb,$image,0,0,0,0,$tw,$th,$w,$h);
            $thumb_file = File::New($fname . "_thumbnail.png");
            $hThumbFile = $thumb_file->GetFileHandle();
            imagepng($thumb, $hThumbFile);
            fclose($hThumbFile);
            $thumb_file->UpdateSize();
            $new_pic_thumb_blob = $thumb_file->blobid;
            //$thumb_file->evaobj->Save();
        }
        $title = "";
        $text = "";
        // TODO: text OCR (tesseract?) and title editing (needed??)
        return self::Create($blobid, $new_pic_thumb_blob, $w, $h, $title, $text, $ext,$filedate,$totaldate);
        
        
        
    }
    /**
     * 
     * @param type $dir
     * @return bool | \Picture
     */
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
        $imagetype = self::GetImageType($filepath);
        if(!$imagetype)
        {
            echo "very bad image<br />";
            File::RejectFile($dir, $filename);
            return true;
        }
        $file = File::IngestFile($dir, $filename);
        $file->filext = $imagetype;
        $pic = self::FromFile($file->blobid);
        if($pic)
        {
            //yay
            echo "added another pic<br />";
            return $pic;
        }
        else
        {
            echo "unfortunately, ". self::$last_error."<br />";
        }
        return true;
    }
    
    public static function FromUpload($uploadArray, $index = -1)
    {
        // get the correct temp file
        $tempname = $index == -1 ? $uploadArray['tmp_name'] : $uploadArray['tmp_name'][$index];
        // is it a picture?
        $imagetype = self::GetImageType($tempname);
        if(!$imagetype)
        {
            // if not, can't do anything
            return null;
        }
        // check for dupes
        $filesize = filesize($tempname);
        $dupeBlobID = File::FindDupe(File::DoHash($tempname),$filesize);
        if($dupeBlobID)
        {
            // re-use the existing file
            return self::FromFile($dupeBlobID);
        }
        // upload
        $newFile = File::Upload($uploadArray, $index);
        if($newFile)
        {
            // use new file if success
            return self::FromFile($newFile->blobid);
        }
        // fail...
        return null;        
    }
    
    public static function XXFromUpload($uploadArray,$index = -1)
    {
        // get uploaded filename, tempname and extension
        $filename = basename($index == -1 ? $uploadArray['name'] : $uploadArray['name'][$index]);
        $tempname = $index == -1 ? $uploadArray['tmp_name'] : $uploadArray['tmp_name'][$index];
        $ext = pathinfo($filename)['extension'];
        $fname = pathinfo($filename)['filename'];
        // this will keep "to be attempted" types for later
        $types = ['png','gif','jpeg',"webp"];
        // init image var
        $image = null;
        // try to create image based on upload extension first
        // remove attempted type from the above array
        switch($ext)
        {
            case "png":
            {
                $image = @imagecreatefrompng($tempname);
                $types = array_diff($types, ['png']);
                break;
            }
            case "gif":
            {
                $image = @imagecreatefromgif($tempname);
                $types = array_diff($types, ['gif']);
                break;
            }
            case "webp":
            {
                $image = @imagecreatefromwebp($tempname);
                $types = array_diff($types, ['webp']);
                break;
            }
            case "jpg":
            case "jpeg":
            {
                $ext = "jpeg";
                $image = @imagecreatefromjpeg($tempname);
                $types = array_diff($types, ['jpeg']);
                break;
            }
        }
        // if no image was produced, try the remaining image types in case of a valid image with wrong extension
        if(!$image)
        {
            // reset the extension
            $ext ="";
            // try whatever formats are left
            foreach($types as $imageformat)
            {
                $imagefx = "imagecreatefrom" . $imageformat;
                $image = @$imagefx($tempname);
                // if a valid image was obtained, note the extension/format and leave the loop
                if($image)
                {
                    $ext = $imageformat;
                    break;
                }
            }
            // if still no valid image was found, give up
            if($ext == "")
            {
                self::$last_error = "Invalid image file";
                return null;
            }
        }
        // if we are here then we have an image
        
        // update filename in case the extension got changed
        $updated_filename = $fname . "." . $ext;
        if($index !== -1)
        {
            $uploadArray['name'][$index] = $updated_filename;
        }
        else
        {
            $uploadArray['name'] = $updated_filename;
        }
        
        // get sizes
        $w = imagesx($image);
        $h = imagesy($image);
        
        // not sure if ever happens but definitely fail in this case
        if($w == 0 || $h == 0)
        {
            self::$last_error = "Invalid image dimensions";
            return null;
        }
        
        $new_pic_file = File::Upload($uploadArray, $index);
        
        if(!$new_pic_file)
        {
            self::$last_error = File::$last_error;
            return null;
        }
        
        $new_pic_blob = $new_pic_file->blobid;
        
        // fit the image inside a MAXIMUM_DIMENSION square
        
        $scale_factor =  self::ScaleThumbnail($w, $h);
        
        // no thumbnail is created if the image is smaller than the square
        
        if($scale_factor > 1)
        {
            // set thumbnail file to the same file
            $new_pic_thumb_blob = $new_pic_file->blobid;
        }
        
        // otherwise, generate a thumbnail, store it as a File and return its blobid
        
        else
        {
            $tw = intval($w*$scale_factor);
            $th = intval($h*$scale_factor);
            $thumb = imagecreatetruecolor($tw,$th);
            imagecopyresampled($thumb,$image,0,0,0,0,$tw,$th,$w,$h);
            $thumb_file = File::New($fname . "_thumbnail.png");
            $hThumbFile = $thumb_file->GetFileHandle();
            imagepng($thumb, $hThumbFile);
            $new_pic_thumb_blob = $thumb_file->blobid;
        }
        $title = "";
        $text = "";
        // TODO: text OCR (tesseract?) and title editing (needed??)
        return self::Create($new_pic_blob, $new_pic_thumb_blob, $w, $h, $title, $text, $ext);
    }
    
    public static function GetGallery($picIDs)
    {
        
        $pictable = EVA::GetAsTable(["blobid","thumb_blobid","picture.width","picture.height","file.extension"],"picture",$picIDs);
        $pics = [];
        foreach($pictable as $picid=>$picdata)
        {
            $pics[]=[
                "id"=>$picid,
                "blob_id"=>$picdata['blobid'],
                "thumbnail_blob_id"=>$picdata['thumb_blobid'],
                "width"=>$picdata['picture.width'],
                "height"=>$picdata['picture.height'],
                "extension"=>$picdata['file.extension'],
            ];
        }
        return $pics;
    }
}
