<?php

function ModuleAction_main_default()
{
	$id=(int) EngineCore::GetSetting('mainpage');
        $revision=KB_Page::GetLastRevision($id);
        $content = "<span style=\"font-size:200px\">:(</span><br />Oopsie woopsie, the index page's gone";
        if($revision !== false)
        {
            $content = $revision['content_html'];
        }
        $t=new TemplateProcessor("kbpage");
	$t->tokens['text']=$content;
	EngineCore::AddPageContent($t->process(true));
	EngineCore::SetPageTitle("Cores main module");
}

function ModuleAction_main_iframe()
{
    // won't be needed once layouts become a thing
    EngineCore::RawModeOn();
    (new TemplateProcessor("fullscreenframe"))->process();
    die();
}
