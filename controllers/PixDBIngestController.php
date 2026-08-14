<?php



namespace Controllers;

use Common\DBHelper;
use Cores\EngineCore;
use Models\File;
use Models\Pictures\Picture;
use Models\Pictures\PictureIngest;
use Models\Pictures\PictureSet;
use PostRoute;
use Route;

/**
 * Description of PixDBIngestController
 *
 * @author admin
 */
class PixDBIngestController
{
    #[Route('pixdb/ingest/view','pixdb.manage')]
    public static function ShowIngest($id = 0, $page = 1)
    {
        $id = intval($id);
        $page=intval($page);
        $pagesize=100;
        $page-=1;
        $ingest = PictureIngest::Load($id);
        if(!$ingest)
        {
            EngineCore::SetPageContent("fuck");
        }
        $picIDs_all = PictureIngest::GetPictures(id: $id);
        $picIDs = array_slice($picIDs_all,$page*$pagesize,$pagesize);
        $pics = Picture::GetGallery($picIDs);
        //$tpl = new TemplateProcessor("pixdb/managerview");
        $q="SELECT id as albumid, title as albumname FROM ".PictureSet::TABLE;
        
        $albums = DBHelper::RunTable($q,[]);
        
        $entity = ['entity_type'=>'pixdb/managerview',
            'page'=>$page+1,
            'iid'=>$id,
            'pictures' => $pics,
            'managermode'=>true,
            'albums'=>$albums,
            'extra_text' => "Viewing ".count($picIDs_all)." ingest results for <strong>{$ingest->folder}</strong>."
        ];
        if($page>0)
        {
            $entity['prev']=$page;
        }
        if(($page+1)*$pagesize<count($picIDs_all))
        {
            $entity['next']=$page+2;
        }
        return $entity;
    }
    
    #[Route('pixdb/ingest/list')]
    public static function ShowIngests()
    {
        return ['entity_type'=>"pixdb/ingestlist",
            'ingests'=>PictureIngest::GetIngests()];
    }
    
    #[Route('pixdb/ingest/create','pixdb.manage')]
    public static function ShowCreateForm()
    {
        return ['entity_type'=>'pixdb/ingestcreate'];
    }
    
    #[Route('pixdb/ingest/manage', 'pixdb.manage')]
    public static function ShowIngestEditor($id = 0)
    {
        $id = intval($id);
        $ingest = PictureIngest::Load($id);
        if(!$ingest)
        {
            EngineCore::GTFO("/pixdb/ingest/list");
        }
        return ['entity_type'=>"pixdb/ingestcreate",
            "visibility"=>$ingest->visibility,
            "active"=>$ingest->active,
            "foldername"=>$ingest->folder,
            "id"=>$ingest->id
        ];
    }
    
    #[PostRoute('pixdb/ingest/create','pixdb.manage')]
    public static function CreateUpdateIngest()
    {
        $id = intval(EngineCore::POST("id", "-1"));
        $foldername=basename(EngineCore::POST("foldername", ""));
        $visibility=EngineCore::POST("visibility", "0");
        $active=EngineCore::POST("active", "0");
        if($id == -1)
        {

            if($foldername==="" || strpbrk($foldername, "\\/?%*:|\"<>") !== FALSE)
            {
                EngineCore::WriteUserError("Invalid directory name [".htmlspecialchars($foldername)."]", "errors");
                EngineCore::GTFO("/pixdb/ingest/create");
            }
            $fullpath = File::FILESTORE_PATH. DIRECTORY_SEPARATOR . File::INGEST_BASE_DIR . DIRECTORY_SEPARATOR . PictureIngest::PICTURE_INGEST_DIR . DIRECTORY_SEPARATOR . $foldername;
            if(file_exists($fullpath) || is_dir($fullpath))
            {
                EngineCore::WriteUserError("Invalid directory name [".htmlspecialchars($foldername)."]", "errors");
                EngineCore::GTFO("/pixdb/ingest/create");
            }
            mkdir($fullpath);
            mkdir($fullpath . DIRECTORY_SEPARATOR . ".failed");
            mkdir($fullpath . DIRECTORY_SEPARATOR . ".dupes");
            $ingest = PictureIngest::Create(folder:$foldername,visibility:$visibility,active:true);
        }
        else
        {
            $ingest = PictureIngest::Load($id);
            if(!$ingest)
            {
                EngineCore::GTFO("/pixdb/ingest/list");
            }
            $ingest->visibility = $visibility;
            $ingest->active = $active;
            $ingest->Save();
        }
        EngineCore::GTFO("/pixdb/ingest/view/" . $ingest->id);
    }
}
