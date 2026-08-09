<?php

namespace Controllers;
use \Route;
use \Common\DBHelper;
use \Models\User\User;
use \Cores\EngineCore;
use \Models\KB\Page;
use \Models\KB\PageInfo;
use \Models\KB\IPageDataProvider;
use \Models\KB\PageDataProviderDB;
use \Models\KB\GroupDBBacker;
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
            $entity = ['entity_type'=>'errors/404', 'message'=>'This KB page does not exist.'];
            return $entity;
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
}
