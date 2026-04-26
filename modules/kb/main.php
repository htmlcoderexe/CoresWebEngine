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

function ModuleAction_kb_view($params)
{
	$id=$params[0];
	$cu=User::GetCurrentUser();
	if($cu->HasPermission('super'))
	{
		EngineCore::AddPageContent((new TemplateProcessor("pagebar,id=$id"))->process(true));
	}
	$pagedata=KB_Page::GetLastRevision($id)['content_html'];
	$t=new TemplateProcessor("kbpage");
	$t->tokens['text']=$pagedata;
	EngineCore::AddPageContent($t->process(true));
}

function ModuleAction_kb_edit($params)
{
	$id=(int)$params[0];
	$pagetext=KB_Page::GetLastRevision($id)['content_raw']??"";
	$t=new TemplateProcessor("pageeditor");
	$t->tokens['pagetext']=$pagetext;
	$t->tokens['pageid']=$id;
	$cu=User::GetCurrentUser();
	if($cu->HasPermission('kb.edit'))
	{
		EngineCore::AddPageContent((new TemplateProcessor("pagebar,id=$id"))->process(true));
	}
	EngineCore::AddPageContent($t->process(true));
} 

function ModuleAction_kb_save($params)
{
    //var_dump($_REQUEST);die;
	$cu=User::GetCurrentUser();
	if(!($cu->HasPermission('kb.edit')))
	{
		EngineCore::SetPageContent("I'm sorry, I'm afraid I can't let you do that.");
		//EngineCore::SetPageContent(var_dump_ob($cu));
		return;
	}
	$text=$_POST['text'];
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
                //var_dump($img);
                //echo $URLs[$i];
            }
        }
        //echo($text);die;
        $id=$_POST['pageid'];
	//Utility::ddump($_POST);
	KB_Page::SaveToDatabase($id,$text);
	//Utility::ddump(mysql_error());
	EngineCore::FromWhenceYouCame();
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