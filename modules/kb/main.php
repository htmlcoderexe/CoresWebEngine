<?php
 
function ModuleAction_kb_default($params)
{
	ModuleAction_kb_index($params);
}

function ModuleAction_kb_project($params)
{
    $projID = intval($params[0]);
    $_SESSION['kb_project_id']=$projID;
    EngineCore::GTFO("/kb/index");
}

function ModuleAction_kb_index($params)
{
	//$content=KB::ListProjects();
	//
        //if(KB::CurrentProjectID() >0)
        //{
    $pid=KB::CurrentProjectID();
            $pages = KB::ListPages($pid);
            $tpl=new TemplateProcessor("kbindex");
            $tpl->tokens['pagelist']=$pages;
            $tpl->tokens['prev']=$pid-1;
            $tpl->tokens['cur']=$pid;
            $tpl->tokens['next']=$pid+1;
            EngineCore::AddPageContent($tpl->process(true));
            return;
        //}//*/
	$cu=User::GetCurrentUser();
	if($cu->HasPermission('super'))
	{
		EngineCore::AddPageContent((new TemplateProcessor("pagebar,id=3"))->process(true));
	}
        $revision=KB_Page::GetLastRevision(3);
        $content = "<span style=\"font-size:200px\">:(</span><br />Oopsie woopsie, the index page's gone";
        if($revision !== false)
        {
            $content = $revision['content_html'];
        }
        $t=new TemplateProcessor("kbpage");
	$t->tokens['text']=$content;
	EngineCore::AddPageContent($t->process(true));
	
}

function ModuleAction_kb_tag($params)
{
    // for now just the one lol
    $tag=$params[0];
    $results = Tag::Find("kbpage",$tag);
    $fields = ['id','title'];
    $q="SELECT " . implode(",",$fields) . " FROM kb_pages WHERE id IN (?". str_repeat(",?", count($results)-1) . ")";
    $pages = DBHelper::RunTable($q,$results);
    $tpl=new TemplateProcessor("kbpagelist");
    $tpl->tokens['pagelist']=$pages;
    $tagtpl=new TemplateProcessor("system/showtags");
    $tagtpl->tokens['tags']=[$tag];
    $tagtpl->tokens['boxid']="tag_box_search";
    EngineCore::AddPageContent($tagtpl->process(true));
    EngineCore::AddPageContent($tpl->process(true));
    return;
    
}

// migrated to proper OOP
function ModuleAction_kb_view($params)
{
	$id=$params[0];
	$page = KB_Page::Load($id);
        if(!$page)
        {
            return;
        }
        $cu=User::GetCurrentUser();
	if($cu->HasPermission('super'))
	{
		EngineCore::AddPageContent((new TemplateProcessor("pagebar,id=$id"))->process(true));
	}
	// $pagedata=KB_Page::GetLastRevision($id)['content_html'];
        $pagedata = $page->GetHTML();
	$t=new TemplateProcessor("kbpage");
	$t->tokens['text']=$pagedata;
        $t->tokens['title']=$page->title;
        $tags = Tag::GetTags($page->id,"kbpage");
        $t->tokens['tags'] = $tags;
        EngineCore::SetPageTitle($page->title);
	EngineCore::AddPageContent($t->process(true));
}

function ModuleAction_kb_edit($params)
{
	$id=(int)$params[0];
	$cu=User::GetCurrentUser();
	if(!$cu->HasPermission('kb.edit'))
	{
            EngineCore::SetPageTitle("Access Denied");
            EngineCore::SetPageContent("I'm sorry, I'm afraid I can't let you do that.");
            return;
	}
	$page = KB_Page::Load($id);
        if(!$page)
        {
            return;
        }
	$pagetext=$page->GetRaw();
	$t=new TemplateProcessor("pageeditor");
	$t->tokens['pagetext']=$pagetext;
	$t->tokens['pageid']=$id;
        $t->tokens['title']=$page->title;
        $tags = Tag::GetTags($page->id,"kbpage");
        $t->tokens['tags'] = $tags;
        EngineCore::SetPageTitle("Editing ".$page->title);
	EngineCore::AddPageContent($t->process(true));
} 

function ModuleAction_kb_save($params)
{
	$cu=User::GetCurrentUser();
	if(!($cu->HasPermission('kb.edit')))
	{
            EngineCore::SetPageContent("I'm sorry, I'm afraid I can't let you do that.");
            return;
	}
        $id=intval($_POST['pageid']);
        $page = KB_Page::Load($id);
        if(!$page)
        {
            return;
        }
	$text=$_POST['text'];
        $title=EngineCore::POST("title","<untitled>");
        $matches=[];
        preg_match_all("/\<img.+src\=(?:\"|\')(.+?)(?:\"|\')(?:.+?)\>/", $text, $matches);
        $URLs = $matches[1];
        for($i=0;$i<count($URLs);$i++)
        {
            $URL=$URLs[$i];
            // local reference, do not redo
            if($URL[0]=="/")
            {
                continue;
            }
            $img = Picture::FromURL($URL);
            if($img)
            {
                $text=str_replace($URL,"/files/stream/{$img->blob_id}/{$img->blob_id}.{$img->extension}",$text);
            }
        }
        $page->title=$title;
        $page->SetBody($text);
        $page->Save();
        EngineCore::GTFO("/kb/view/".$id);
}

function ModuleAction_kb_create($params)
{
	$cu=User::GetCurrentUser();
	if(!($cu->HasPermission('kb.create')))
	{
		EngineCore::SetPageContent("I'm sorry, I'm afraid I can't let you do that.");
		EngineCore::Dump2Debug($cu);
		return;
	}
	if(!isset($_POST['title']))
	{
		$t=new TemplateProcessor("newpage");
		EngineCore::SetPageContent($t->process(true));
	}
	else
	{
		$id=KB_Page::CreatePage($_POST['title']);
		
		EngineCore::GTFO("/kb/edit/".$id);
	}
}