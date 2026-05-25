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
        $revision=KBPage::GetLastRevision(3);
        $content = "<span style=\"font-size:200px\">:(</span><br />Oopsie woopsie, the index page's gone";
        if($revision !== null)
        {
            $content = $revision->html;
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
	$page = KBPage::Load($id);
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
        $pagedata = $page->html;
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
	$page = KBPage::Load($id);
        if(!$page)
        {
            return;
        }
	$t=new TemplateProcessor("pageeditor");
	$t->tokens['ejsdoc']=json_encode($page->ejsdoc);
	$t->tokens['pageid']=$id;
        $t->tokens['title']=$page->title;
        $tags = Tag::GetTags($page->id,"kbpage");
        $t->tokens['tags'] = $tags;
        EngineCore::SetPageTitle("Editing ".$page->title);
	EngineCore::AddPageContent($t->process(true));
}

function _p($text)
{
    return;
    echo "<br>$text<br>";
}
function ModuleAction_kb_test($params)
{
    if(!EngineCore::$CurrentUser->HasPermission("super"))
    {
        return;
    }
    
    $testGroup = new KBGroupTest();
    $testGroup->Run();
    $testGroup->PrintResults();
    
    /*
    KBGroupTest::TestFind();
    KBGroupTest::TestLoad();
    KBGroupTest::TestSave1();
    KBGroupTest::TestAdd();
    KBGroupTest::TestRemove();
    KBGroupTest::TestMove();
    KBGroupTest::TestMoveWithDB();
    //*/
    die;
    return;
        DBHelper::RunStmt("TRUNCATE TABLE `intranet_basic`.`kbgroups`", []);
        
        $g11 = KBPageSequence::Create(11);
        $g11->AddPage(8,null,null);
        var_dump($g11);
        $g11->AddPage(7,null,null);
        var_dump($g11);
        $g11->AddPage(10,null,8);
        $g11->pages->SaveToDB();
        var_dump($g11);
        
        //return;
        $ddd=['TEST','TEST'];
        _p("loading collection");
        $col = OrderedDBCollection::Load("kbgroups","test1",['prev','next']);
        $col->AddItem(100,$ddd);
        $col->AddItem(101,$ddd);
        _p("added items, saving");
        $col->SaveToDB();
        _p("col looks like");
        var_dump($col);
        _p("loading col2");
        $col2=OrderedDBCollection::Load("kbgroups","test1",['prev','next']);
        _p("col2 looks like");
        var_dump($col2);
        _p("loading col3");
        $col3=OrderedDBCollection::Load("kbgroups","test2",['prev','next']);
        $col3->AddItem(102,$ddd);
        $col3->AddItem(103,$ddd);
        _p("added items to col3, col3 looks like");
        var_dump($col3);
        _p("saving col3 to db");
        $col3->SaveToDB();
        _p("adding item in col2");
        $col2->AddItem(104,$ddd,0);
        var_dump($col2);
        _p("saving col2 to db");
        
        $col2->SaveToDB();
        die;
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
        $page = KBPage::Load($id);
        if(!$page)
        {
            return;
        }
	$text=$_POST['text'];
        $title=EngineCore::POST("title","<untitled>");
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
		$id=KB::CreatePage($_POST['title']);
		
		EngineCore::GTFO("/kb/edit/".$id);
	}
}
function ModuleAction_kb_api($params)
{
    require "api.php";
    return Module::SPLIT_ROUTE;
}
function ModuleAction_kb_migrate($params)
{
    $cu=User::GetCurrentUser();
	if(!($cu->HasPermission('super')))
        {
            return;
        }
    require "migrate.php";
    return Module::SPLIT_ROUTE;
}