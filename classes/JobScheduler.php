<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of JobScheduler
 *
 * @author admin
 */
//require_once "../modules/pixdb/Picture.php";

class JobScheduler
{
    public const JOBS_BASE = 20;
    public static function Schedule($type, $params)
    {
        DBHelper::Insert("scheduled_jobs", [null, $type,$params,0]);
    }
    
    public static function CrankJobs()
    {
        // count still ongoing jobs
        $active_jobs = DBHelper::Count("scheduled_jobs","state",['state'=>1]);
        // subtract half of "leftovers" from previous run to converge on
        // a reasonable value based on system load
        // do at least one job anyway
        $jobs_to_do = max(1, self::JOBS_BASE - ($active_jobs/2));
        // unless there are as many as the maximum amount taken from previous run
        if($active_jobs >= self::JOBS_BASE)
        {
            EngineCore::RawModeOn();
            echo "too many jobs running...";
            die();
        }
        for($i = 0;$i<$jobs_to_do;$i++)
        {
            self::DoJob();
        }
    }
    
    public static function DoJob()
    {
        $q=DBHelper::Select("scheduled_jobs", ['id','jobtype','params'], ['state'=>0]);
        $job = DBHelper::RunRow($q,[0]);
        if(!$job)
        {
            EngineCore::RawModeOn();
            die("no jobs");
        }
        DBHelper::Update("scheduled_jobs",['state'=>1],['id'=>$job['id']]);
        switch($job['jobtype'])
        {
            case "tesseract":
            {
                $filepath = File::GetFilePath($job['params']);
                $result =[];
                $cmd = "tesseract " . $filepath . " stdout -l eng+rus+pol+nor+nld";
                exec($cmd,$result);
                $text = implode("\r\n",$result);
                $eid = EVA::GetByProperty("blobid",$job['params'],"picture");
                EVA::WritePropByName($eid[0],"picture.text",$text);
                DBHelper::Update("scheduled_jobs",['state'=>2],['id'=>$job['id']]);
                EngineCore::RawModeOn();
                echo "OCR for file {$job['params']} complete.<br />Command: <pre>$cmd</pre><br /><pre> $text </pre>";
                break;
            }
        }
    }
}
