<?php

namespace Controllers;
use \Route;

use \Cores\EngineCore;

use \Common\DBHelper;
use Common\EditorJS\Document as EditorJSDocument;

use \Models\User\User;

use \Models\KB\Page;
use \Models\KB\PageDataProviderDB;
use \Models\KB\GroupDBBacker;

use \Models\Tags\Tag;

use \Models\Pictures\Picture;
/**
 * Description of KBPageController
 *
 * @author admin
 */
class KBPageController
{
    #[Route('kb/view')]
    public static function ViewPage($id = 0)
    {
        $id=intval($id);
        $provider = new PageDataProviderDB(pageTable: 'kb_pages', revisionTable: 'kb_page_revisions');
        $gdb = new GroupDBBacker(tablename: 'kb_groups');
        $page = Page::Load(provider: $provider, groupDb: $gdb, id: $id);
        if(!$page)
        {
            return EngineCore::Error(404, 'This KB page does not exist.');
        }
        $entity = get_object_vars($page);
        $entity['entity_type']="kb/page";
        $cu=User::GetCurrentUser();
	if($cu->HasPermission('super'))
	{
            $entity['editlinks'] = 'true';
	}
        $tags = \Models\Tags\Tag::GetTags($page->id,"kbpage");
        $entity['tags'] = $tags;
        EngineCore::SetPageTitle($page->title);
        return $entity;
    }
    
    #[Route('kb/edit', 'kb.edit')]
    public static function ShowPageEditor($id = 0)
    {
        $id=intval($id);
        $provider = new PageDataProviderDB(pageTable: 'kb_pages', revisionTable: 'kb_page_revisions');
        $gdb = new GroupDBBacker(tablename: 'kb_groups');
        $page = Page::Load(provider: $provider, groupDb: $gdb, id: $id);
        if(!$page)
        {
            return EngineCore::Error(404, 'This KB page does not exist.');
        }
        $entity = get_object_vars($page);
        
	$entity['entity_type']="kb/editor";
	$entity['ejsdoc']=json_encode($page->ejsdoc);
	$tags = Tag::GetTags($page->id,"kbpage");
        $entity['tags'] = $tags;
        EngineCore::SetPageTitle("Editing ".$page->title);
	return $entity;
    }
    
    #[Route('kb/save','kb.edit')]
    public static function SavePage()
    {
        $def = [
            'pageid'=>0,
            'text'=>'',
            'title'=>'<untitled>'
        ];
        $submission = EngineCore::GetSubmission($def);
        if(!$submission)
        {
            return;
        }
        $id=intval($submission['pageid']);
        $provider = new PageDataProviderDB(pageTable: 'kb_pages', revisionTable: 'kb_page_revisions');
        $gdb = new GroupDBBacker(tablename: 'kb_groups');
        $page = Page::Load(provider: $provider, groupDb: $gdb, id: $id);
        if(!$page)
        {
            return EngineCore::Error(404);
        }
	$text=$submission['text'];
        $title=$submission['title'];
        $postObj = EditorJSDocument::FromJSON($text);
        if(!$postObj)
        {
            // throw an error idk
        }
        $URLs = $postObj->images;
        $map = [];
        for($i=0;$i<count($URLs);$i++)
        {
            $URL=$URLs[$i];
            // local reference, do not redo
            if(substr($URL,0,strlen(BASE_URI))===BASE_URI)
            {
                continue;
            }
            $img = Picture::FromURL($URL);
            if($img)
            {
                $map[$URL] = BASE_URI."/files/stream/{$img->blob_id}/{$img->blob_id}.{$img->extension}";
            }
        }
        if(count($map)>0)
        {        
            $postObj->UpdateImages($map);
        }
        $page->title=$title;
        $page->ejsdoc = $postObj;
        $page->ProcessPage();
        $page->SaveNewRevision();
        $page->Save();
        //var_dump($page);die;
        EngineCore::GTFO("/kb/view/".$id);
        die;
    }
}
