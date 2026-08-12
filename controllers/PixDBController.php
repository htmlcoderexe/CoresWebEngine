<?php

namespace Controllers;

use Cores\EngineCore;
use Cores\JobScheduler;
use Cores\TemplateProcessor;
use Models\Pictures\Picture;
use Models\Tags\Tag;
use Route;

/**
 * Description of PixDBController
 *
 * @author admin
 */
class PixDBController
{
    #[Route('pixdb/view')]
    public static function ShowImage($id = 0)
    {
        $id = intval($id);
        $pic = Picture::Load($id);
        if(!$pic)
        {
            return EngineCore::Error(404);
        }
        $entity = get_object_vars($pic);
        $entity['entity_type']='pixdb/pic';
        $tags = Tag::GetTags($pic->id,'picture');
        $entity['tags'] = $tags;
        return $entity;
    }
    #[Route('pixdb/retesseract', 'pixdb.manage')]
    public static function RedoTesseractOCR($id = 0)
    {
        $id = intval($id);
        $pic = Picture::Load($id);
        if(!$pic)
        {
            return EngineCore::Error(404);
        }
        $redo_lang = EngineCore::GetSubmission(["redo_lang"=>""]);
        if($redo_lang)
        {
            JobScheduler::Schedule("tesseract", $pic->blob_id);
            EngineCore::GTFO("/pixdb/view/$id");
            return;
        }
    }
}
