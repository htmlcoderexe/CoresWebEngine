<?php


/**
 * Provides functionality to extract data from EPUB files.
 *
 * @author admin
 */
class EpubParser
{
    private ZipArchive $zip;
    private SimpleXMLElement $container;
    private SimpleXMLElement $manifest;
    private string $path = "";
    private string $basepath = "";
    public string $title;
    public string $cover_image_data="";
    public string $cover_image_type="";
    public string $author = "";
    public string $error = "";
    public string $description = "";
    public function __construct(string $filename)
    {
        
        $this->zip = new ZipArchive();
        if($this->zip->open($filename)!==TRUE)
        {
            $this->error="Bad file: not zip";
            return;
        }
        $this->path = "zip://".$filename."#";
        $containerfile = file_get_contents($this->path."META-INF/container.xml");
        if(!$containerfile)
        {
            $this->error = "File missing container manifest";
            return;
        }
        $this->container = simplexml_load_string($containerfile);

        if(!$this->container)
        {
            $this->error = "Bad container manifest";
            return;
        }
        $opfname = $this->container->rootfiles->rootfile->attributes()['full-path'];
        if(!$opfname)
        {
            $this->error = "OPF file not specified";
            return;
        }
        $pinfo = pathinfo($opfname);
        $this->basepath = $pinfo['dirname'];
        // if root dir, dir path will be "." and zip proto won't like it
        if($this->basepath==".")
        {
            $this->basepath="";
        }
        else
        {
            $this->basepath.="/";
        }
        $opffile = file_get_contents($this->path.$opfname);
        $this->manifest = simplexml_load_string($opffile);
        if(!$this->manifest)
        {
            $this->error = "Invalid manifest file";
            return;
        }
        $this->title = self::GetTitle($this->manifest);
        $this->author = self::GetAuthor($this->manifest);
        $this->description = self::GetDescription($this->manifest);
        $coverdata = self::GetCover($this->manifest);
        if($coverdata)
        {
            $this->cover_image_type = $coverdata['type'];
            $this->cover_image_data = file_get_contents($this->path.$this->basepath.$coverdata['path']);
        }
    }
    
    public static function GetCover(SimpleXMLElement $opf)
    {
        $coverid="";
        foreach($opf->metadata->children() as $meta)
        {
            $attributes = $meta->attributes();
            if($meta->getName() =='meta')
            {

                $prop = $attributes['name'];
                $val = $attributes['content'];
                switch($prop)
                {
                    case 'cover':
                    {
                        $coverid = $val;
                        break;
                    }
                    default:
                    {
                        break;
                    }
                }
            }
        }
        if(!$coverid)
        {
            return null;
        }
        foreach($opf->manifest->children() as $item)
        {
            $a=$item->attributes();
            if((string)$a['id']==$coverid)
            {
                return ['path' => $a['href'],
                'type' => $a['media-type']];
            }
        }
    }
    
    public static function GetTitle(SimpleXMLElement $opf)
    {
        foreach($opf->metadata->children('dc',TRUE) as $dc)
        {
            $attributes = $dc->attributes();
            if($dc->getName() =='title')
            {
                return (string)$dc;
            }

        }
        return "[Untitled]";
    }
    
    public static function GetAuthor(SimpleXMLElement $opf)
    {
        foreach($opf->metadata->children('dc',TRUE) as $dc)
        {
            $attributes = $dc->attributes();
            if($dc->getName() =='creator')
            {
                return (string)$dc;
            }
        }
        return "[Unknown Author]";
    }
    public static function GetDescription(SimpleXMLElement $opf)
    {
        foreach($opf->metadata->children('dc',TRUE) as $dc)
        {
            $attributes = $dc->attributes();
            if($dc->getName() =='description')
            {
                return (string)$dc;
            }
        }
        return "";
    }
}
