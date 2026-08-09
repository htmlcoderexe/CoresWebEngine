<?php
namespace Controllers;

use Cores\EngineCore;
use Cores\TemplateProcessor;
use Cores\JobScheduler;

use Models\KB\Page;
use Models\KB\PageDataProviderDB;

use Models\MusicTrack;
use Models\Documents\Document;
use Models\Pictures\PictureIngest;


class HomePage
{
    #[\Route('main/default')]
    public static function Homepage()
    {
        $id=(int) EngineCore::GetSetting('mainpage');
        $provider = new PageDataProviderDB(pageTable: 'kb_pages', revisionTable: 'kb_page_revisions');
        $revision=Page::GetLastRevision(pageId: $id, provider: $provider);
        $content = "<span style=\"font-size:200px\">:(</span><br />Oopsie woopsie, the index page's gone";
        if($revision === null)
        {
            return EngineCore::Error(500);
        }
        $entity = (array)$revision;
        $entity['entity_type'] = 'kb/page';
	EngineCore::SetPageTitle("Cores main module");
        return $entity;
    }
    #[Route('main/iframe')]
    public static function ShowFullScreen()
    {
        EngineCore::RawModeOn();
        (new TemplateProcessor("fullscreenframe"))->process();
        die();
    }
    #[Route('main/crankjobs')]
    public static function CrankJobs($key = '')
    {
        //TODO: add "api key" style verification
        set_time_limit(0);
        $time_start = hrtime(true);
        echo "<pre>";
        JobScheduler::CrankJobs();
        flush();
        $ingestorobjects = PictureIngest::GetIngests(true);
        $stillrunning = [];
        /*/
        $picingests = EVA::GetByProperty("active", "1", "picture.ingest");
        foreach($picingests as $ingestid)
        {
            $ingest = PictureIngest::Load($ingestid);
            if($ingest)
            {
                $ingestorobjects[]=$ingest;
            }
        }
        //*/
        $time_max = $time_start + 60000000000;
        $mp3idling = false;
        $epubidling = false;
        while(hrtime(true)<$time_max)
        {
            if(!$mp3idling)
            {
                $mp3idling = !MusicTrack::Ingest("mp3");
                flush();
            }
            if(!$epubidling)
            {
                $epubidling = !Document::IngestEpub("epub");
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
            if(count($ingestorobjects)<1 && $mp3idling && $epubidling)
            {
                break;
            }
        }

        $time_final = hrtime(true);
        $diff = ($time_final-$time_start)/1000000;
        echo "Completed in $diff milliseconds.</pre>";
    }
    
    
}
