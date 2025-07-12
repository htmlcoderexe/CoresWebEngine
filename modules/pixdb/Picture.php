<?php

Module::DemandProperty("thumb_blobid", "Thumbnail File ID", "File ID of the thumbnail image.");
Module::DemandProperty("picture.text", "Image text", "Text contained in the image, may be automatically generated.");
Module::DemandProperty("picture.width", "Width", "Image width, in pixels.");
Module::DemandProperty("picture.height", "Height", "Image height, in pixels.");
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
    
    private $eva;
    
    public function __construct($id)
    {
        $eva = new EVA($id);
        $this->id = $eva->id;
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
    
    public static function Create($blob, $thumb, $w, $h, $title, $text, $ext)
    {
        $eva = EVA::CreateObject("picture");
        $eva->AddAttribute("blobid", $blob);
        $eva->AddAttribute("thumb_blobid", $thumb);
        $eva->AddAttribute("picture.width",$w);
        $eva->AddAttribute("picture.height",$h);
        $eva->AddAttribute("title",$title);
        $eva->AddAttribute("picture.text",$text);
        $eva->AddAttribute("file.extension",$ext);
        $eva->Save();
        return new Picture($eva->id);
    }
    
    public static function FromUpload($uploadArray,$index = -1)
    {
        // get uploaded filename, tempname and extension
        $filename = basename($index == -1 ? $uploadArray['name'] : $uploadArray['name'][$index]);
        $tempname = $index == -1 ? $uploadArray['tmp_name'] : $uploadArray['tmp_name'][$index];
        $ext = pathinfo($filename)['extension'];
        $fname = pathinfo($filename)['filename'];
        // this will keep "to be attempted" types for later
        $types = ['png','gif','jpeg'];
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
}
