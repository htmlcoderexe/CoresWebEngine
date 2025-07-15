<?php
require_once "KB_Page.php";
define("KB_JOB_TYPE_PAGEUPDATE",0);
define("KB_JOB_PRIORITY_LOW",0);
define("KB_JOB_PRIORITY_NORMAL",1);
define("KB_JOB_PRIORITY_ELEVATED",2);
define("KB_JOB_PRIORITY_HIGH",3);
define("KB_JOB_PRIORITY_CRITICAL",4);
define("KB_JOB_ARGUMENT_NONE",0);
class KB
{

	public static function ListProjects()
	{
		$buffer="";
		$projectlist=DBHelper::RunTable(DBHelper::Select('KB_projects',['id','name']),[]);
		foreach($projectlist as $project)
		{
			$buffer.= $project['name']."<br />";
		}
		$ss=new StringSet(1,1,1);
		$page=new KB_Page(1);
		return $page->GetRaw();
	//	return $buffer;
		//return "<pre>start job: <1B>3<0F1B>p<00C8FA>\r\nend job<1B>e<101B>i</pre>";
	}
	public static function ScheduleJob($jobtype,$pageaffected,$param,$priority)
	{
		$time=(int)time();
		$row=Array(
			null,
			$jobtype,
			$pageaffected,
			$param,
			$time,
			$priority
		);
		DBHelper::Insert("kb_job_queue",$row);
	}
	public static function EnqeuePageUpdate($pageid)
	{
		KB::ScheduleJob(KB_JOB_TYPE_PAGEUPDATE,$pageid,KB_JOB_ARGUMENT_NONE,KB_JOB_PRIORITY_NORMAL);
	}
}

