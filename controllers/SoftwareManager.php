<?php

namespace Controllers;

use Cores\EngineCore;
use Models\File;
use Models\Pictures\PictureSet;
use Models\Software\Package;
use Models\Software\Publisher;
use Models\Software\Release;
use Models\Software\ReleaseFile;
use PostRoute;
use Route;

/**
 * Description of SoftwareManager
 *
 * @author admin
 */
class SoftwareManager
{
    #[Route('software/newrelease','software.manage')]
    public static function ShowReleaseSubmitForm($id = 0)
    {
        $id = intval($id);
        $sw = Package::Load($id);
        if(!$sw)
        {

            return EngineCore::Error(404, 'The software package does not exist.');
        }
        return ['entity_type' => 'software/editrelease',
            'software_id' => $id,
            'software_name' => $sw->title
            ];
    }
    #[Route('software/new', 'software.manage')]
    public static function ShowNewSoftwareForm()
    {
        return ['entity_type' => 'software/editsoftware',
            'publishers' => Publisher::GetList()
        ];
    }
    #[PostRoute('software/savepackage','software.manage')]
    public static function CreateOrUpdatePackage()
    {
        $id = intval(EngineCore::POST('id',-1));
        $title = EngineCore::POST('title','');
        $icon = EngineCore::POST('icon','');
        $description = EngineCore::POST('description','');
        $type = intval(EngineCore::POST('type',0));
        $category = intval(EngineCore::POST('category',0));
        $publisher = intval(EngineCore::POST('publisher',0));
        
        $uid = EngineCore::$CurrentUser->userid;
        $gid = 0;
        
        // create the publisher if requested
        if($publisher == -1)
        {
            $pname = EngineCore::POST('pubname','Unknown');
            $pdesc = EngineCore::POST('pubdesc');
            $p = Publisher::Create($pname, $pdesc, '');
            $publisher = $p->id;
        }
        
        // create package if requested
        if($id==-1)
        {   
            $album = PictureSet::Create("Screenshots", "Screenshots for ".$title);
            $screenshot_album = $album->id;
        
            // create new
            $package = Package::Create($title, $description, $icon, $screenshot_album, $category, $publisher, $type, $uid, $gid);
            EngineCore::GTFO('/software/view/'.$package->id);
        }
        else
        {
            // edit existing
        }
    }
    
    #[PostRoute('software/saverelease','software.manage')]
    public static function CreateOrUpdateRelease()
    {
        $id = intval(EngineCore::POST('id',-1));
        $software_id = intval(EngineCore::POST('software_id',-1));
        $version = EngineCore::POST('version','');
        $description = EngineCore::POST('description','');
        $type = intval(EngineCore::POST('type',0));
        $time = time();
        $files_in = $_FILES['release_files']??[];
        $comments_in = EngineCore::Post('file_comments');
        if($id==-1)
        {
            // create new
            $release = Release::Create($software_id, $version, $description, $type, $time);
            if(isset($files_in['name']))
            {
                for($i=0;$i<count($files_in['name']);$i++)
                {
                    $file = File::Upload($files_in, $i);
                    if($file)
                    {
                        $fileattach = ReleaseFile::Create($release->id, $file->blobid, $comments_in[$i]);

                    }
                }
            }
            EngineCore::GTFO('/software/view/'.$software_id."/".$release->id);

        }
        else
        {
            // edit existing
        }
    }
}
