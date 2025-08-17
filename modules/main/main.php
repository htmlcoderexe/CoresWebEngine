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

require "tag.php";
function ModuleAction_main_tag($params)
{
    return Module::SPLIT_ROUTE;
}
require "chip.php";
function ModuleAction_main_chip($params)
{
    return Module::SPLIT_ROUTE;
}

function ModuleAction_main_crankjobs($params)
{
    set_time_limit(0);
    $time_start = hrtime(true);
    echo "<pre>";
    JobScheduler::CrankJobs();
    flush();
    
    $picingests = EVA::GetByProperty("active", "1", "picture.ingest");
    $ingestorobjects = [];
    $stillrunning = [];
    foreach($picingests as $ingestid)
    {
        $ingest = PictureIngest::Load($ingestid);
        if($ingest)
        {
            $ingestorobjects[]=$ingest;
        }
    }
    
    $time_max = $time_start + 60000000000;
    $mp3idling = false;
    while(hrtime(true)<$time_max)
    {
        if(!$mp3idling)
        {
            $mp3idling = !MusicTrack::Ingest("mp3");
            flush();
        }
        $stillrunning = [];
        foreach($ingestorobjects as $ingest)
        {
            if($ingest->Run())
            {
                $stillrunning[]=$ingest;
            }
        }
        $ingestorobjects = $stillrunning;
        if(count($ingestorobjects)<1 && $mp3idling)
        {
            break;
        }
    }
    
    $time_final = hrtime(true);
    $diff = ($time_final-$time_start)/1000000;
    echo "Completed in $diff milliseconds.</pre>";
}