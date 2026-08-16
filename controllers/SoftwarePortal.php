<?php


namespace Controllers;

use Common\DBHelper;
use Cores\EngineCore;
use Models\File;
use Models\Software\Package;
use Models\Software\Publisher;
use Models\Software\Release;
use Route;

/**
 * Description of SoftwarePortal
 *
 */
class SoftwarePortal
{
    #[Route('software/all')]
    public static function ShowAllDownloads()
    {
        return ['entity_type'=>'software/list',
            'items'=>Package::GetList(),
            'extra_text'=>'<h2>All software</h2>'
        ];
    }
    
    #[Route('software/publishers')]
    public static function ShowPublisherOrList($id = 0)
    {
        $id = intval($id);
        $publisher = Publisher::Load($id);
        if($publisher)
        { 
            return ['entity_type'=>'software/list',
                'items'=>Package::GetList(['publisher'=>$id]),
                'extra_text'=>'<h2>Software by '.$publisher->name.'</h2>'
            ];
        }
        else
        {
            $tg = Publisher::TABLE;
            $tt = Package::TABLE;
            $qc = "SELECT $tg.id as pid, $tg.name as name, COUNT($tt.id) as swcount, $tg.description as description "
                    . "FROM $tg LEFT JOIN $tt "
                    . "ON $tg.id = $tt.publisher "
                    . "GROUP BY $tg.id "
                    . "ORDER BY swcount ";
            $publishers = DBHelper::RunTable($qc, []);
            return ['entity_type'=>"software/listpublishers",
                'publishers'=>$publishers];
        }
    }
    
    #[Route('software/view')]
    public static function ShowPackage($id = 0, $rid = 0)
    {
        $id = intval($id);
        $rid = intval($rid);
        $sw = Package::Load($id);
        $release = Release::Load($rid);
        if(!$sw)
        {
            return EngineCore::Error(404, 'The software package does not exist.');
        }
        if($release && $release->software_id == $id)
        {
            $e = (array)$release;
            $e['entity_type']="software/viewrelease";
            $e['software_name']= $sw->title;
            $files = [];
            foreach($release->files as $file)
            {
                $fp = File::Load($file->blobid);
                if(!$fp)
                {
                    continue;
                }
                $flat = (array)$fp;
                $flat['comment'] = $file->comment;
                $files[]=$flat;
            }
            $e['files']=$files;
            return $e;
        }
        $e = (array)$sw;
        $e['entity_type']="software/view";
        $publisher = Publisher::Load($sw->publisher);
        if($publisher)
        {
            $e['publisher_name'] = $publisher->name;
            $e['publisher_description'] = $publisher->description;
            $e['publisher_id'] = $publisher->id;
        }
        return $e;
    }
}
