<?php

namespace Controllers;
use \Route;

use \Cores\EngineCore;

use \Common\DBHelper;
use \Common\EditorJS\Document as EditorJSDocument;
use \Common\HTTPHeaders;

use \Models\User\User;

use \Models\KB\Page;
use \Models\KB\PageSequence;   
use \Models\KB\PageDataProviderDB;
use \Models\KB\GroupDBBacker;
use \Models\KB\Manager;

use \Models\Tags\Tag;

use \Models\Pictures\Picture;
/**
 * Description of KBPageController
 *
 * @author admin
 */
class KBPageController
{
    #[Route('kb/default')]
    public static function Index()
    {
        $pid=Manager::CurrentProjectID();
        $pages = Manager::ListPages($pid);
        $entity = ['entity_type'=>'kb/index',
            'pages'=>$pages,
            'prev'=>$pid-1,
            'cur'=>$pid,
            'next'=>$pid+1
            ];
        return $entity;
    }
    
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
    
    #[Route('kb/create','kb.create')]
    public static function CreatePage()
    {
        $def = ['title'=>'Untitled Page'];
        $sub = EngineCore::GetSubmission($def);
	if(!$sub)
	{
            return [
                'entity_type'=>'kb/create'
            ];
        }
	else
	{
            $id=\Models\KB\Manager::CreatePage($sub['title']);

            EngineCore::GTFO("/kb/edit/".$id);
            die;
	}
    }
    #[Route('kb/tag')]
    public static function ListByTag($tag = '')
    {
            // for now just the one lol
        $results = Tag::Find("kbpage",$tag);
        $fields = ['id','title'];
        $q="SELECT " . implode(",",$fields) . " FROM kb_pages WHERE id IN (?". str_repeat(",?", count($results)-1) . ")";
        $pages = DBHelper::RunTable($q,$results);
        if(!$pages)
        {
            $pages = [];
        }
        $entity = ['entity_type'=>'pagelist'];
        $entity['pages']=$pages;
        $entity['entity_type'] = 'kb/pagelist';
        $entity['tags']=[$tag];
        return $entity;
    }
    #[Route('kb/project')]
    public static function SetProject($id = 0)
    {
        $projID = intval($id);
        Manager::SwitchProject($projID);
        EngineCore::GTFO("/kb");
    }
    #[Route('kb/info')]
    public static function PageInfo($id = '')
    {
        $id = intval($id);
        $provider = new PageDataProviderDB(pageTable: 'kb_pages', revisionTable: 'kb_page_revisions');
        $gdb = new GroupDBBacker(tablename: 'kb_groups');
        $page = Page::Load(provider: $provider, groupDb: $gdb, id: $id);
        $data = null;
        if($page)
        {
           $data = [
               'title'=>$page->title,
               'id'=>$page->id,
               'excerpt'=>substr($page->text,0,150),
               'isIndex'=>false
           ];
           if(PageSequence::Exists($page->id))
           {
               $data['isIndex'] = true;
           }
           HTTPHeaders::Status(200);
           EngineCore::EmitJSON($data);

        }
        HTTPHeaders::Status(404);
        EngineCore::EmitJSON($data);
    }
    
    #[Route('kb/suggest')]
    public static function SearchPageTitle($prefix = '')
    {
        $data = [];
        if($prefix != "")
        {

            $query_params = ["%" . $prefix . "%"];
            $q = "SELECT title,id FROM kb_pages WHERE title LIKE ?";
            $data = DBHelper::RunTable($q,$query_params);
        }
        HTTPHeaders::Status(200);
        EngineCore::EmitJSON($data);
    }
}
