<?php

function ModuleAction_main_default()
{
	$id=(int)Utility::GetSetting('mainpage');
        $revision=KB_Page::GetLastRevision($id);
        $content = "<span style=\"font-size:200px\">:(</span><br />Oopsie woopsie, the index page's gone";
        if($revision !== false)
        {
            $content = $revision['content_html'];
        }
        $t=new TemplateProcessor("kbpage");
	$t->tokens['text']=$content;
	Utility::AddPageContent($t->process(true));
	Utility::SetPageTitle("Cores main module");
}

