<?php
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;
global $_DEBUG;
$_DEBUG=true;
ini_set("display_errors", "1");
error_reporting(E_ALL & ~E_NOTICE);

global $_CURRENT_USER;
global $_PAGE_CONTENT;
global $_PAGE_SIDEBAR;
global $_PAGE_TITLE;
global $_DEBUG_INFO;
global $_PAGE_STYLESHEETS;
global $_PAGE_SCRIPTS;
global $_PAGE_RAW;

// TODO make this detect the missing file and do a first run wizard thing

// consult the file "config.example.php" for what is needed
require_once "config.php"; 

require_once CLASS_DIR."Utility.php";
require_once CLASS_DIR."StringSet.php";
require_once CLASS_DIR."DBHelper.php";
require_once CLASS_DIR."Logger.php";
require_once CLASS_DIR."TemplateProcessor.php";
require_once CLASS_DIR."User.php";
require_once CLASS_DIR."User/UserExtendedProps.php";
require_once CLASS_DIR."Module.php";
require_once CLASS_DIR."AuthHelper.php";
require_once CLASS_DIR."KB.php";
require_once CLASS_DIR."KB_Page.php";
//require_once CLASS_DIR."Datacore/DataPoint.php";
//require_once CLASS_DIR."Datacore/DataSeries.php";
//require_once CLASS_DIR."Datacore/Study.php";
//require_once CLASS_DIR."Datacore/Plotter.php";
//require_once CLASS_DIR."Game/Player.php";
require_once CLASS_DIR."EVA.php";
require_once CLASS_DIR."CalendarScheduler.php";
require_once CLASS_DIR."CalendarEvent.php";
require_once CLASS_DIR."File.php";
require_once CLASS_DIR."HTTPHeaders.php";
require_once CLASS_DIR."Tag.php";


session_start();
$_CURRENT_USER=User::GetCurrentUser();
$_PAGE_SIDEBAR=Array();
require_once CLASS_DIR."Router.php";
Router::Dispatch();

Utility::debug("<strong>Route:</strong>".Utility::GET("route"));

if($_DEBUG)
{
    Utility::PageSidebarAdd("Debug info", $_DEBUG_INFO);
}

//sidebar
$aerr="";
if(isset($_SESSION['autherror']))
{
	$aerr=",aerr=Incorrect username/password";
	unset($_SESSION['autherror']);
}
Utility::PageSidebarAdd("&nbsp;", (new TemplateProcessor("membercard".$aerr))->process(true),"/userpanel");



//any output only below this line

    
if($_PAGE_RAW)
{
    die($_PAGE_CONTENT);
}
$tpl=new TemplateProcessor("mainpage");
$tpl->tokens['title']=$_PAGE_TITLE;
$tpl->tokens['content']=$_PAGE_CONTENT;
$tpl->tokens['sidebar']=implode("\r\n\n",$_PAGE_SIDEBAR);
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$finish = $time;
$total_time = round(($finish - $start), 4);
$data=$tpl->process(true);
echo str_replace("||||generatedtime||||",$total_time,$data);
//$tpl->dump();