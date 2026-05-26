<?php

// TODO make this detect the missing file and do a first run wizard thing

// consult the file "config.example.php" for what is needed
require_once "config.php"; 

$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;
global $_DEBUG;
$_DEBUG=true;
require_once CLASS_DIR."EngineCore.php";
// EngineCore::$DEBUG = true;
ini_set("display_errors", "1");
error_reporting(E_ALL & ~E_NOTICE);
setlocale(LC_CTYPE, "en_US.UTF-8");

global $_PAGE_CONTENT;
global $_PAGE_SIDEBAR;
global $_PAGE_TITLE;
global $_DEBUG_INFO;
global $_PAGE_STYLESHEETS;
global $_PAGE_SCRIPTS;


require_once CLASS_DIR."Utility.php";
require_once CLASS_DIR."StringSet.php";
require_once CLASS_DIR."DBHelper.php";
require_once CLASS_DIR."Logger.php";
require_once CLASS_DIR."TemplateProcessor.php";
require_once CLASS_DIR."User.php";
require_once CLASS_DIR."UserGroup.php";
require_once CLASS_DIR."User/UserExtendedProps.php";
require_once CLASS_DIR."Module.php";
require_once CLASS_DIR."AuthHelper.php";
require_once CLASS_DIR."OrderedDBCollection.php";
require_once CLASS_DIR."KB.php";
require_once CLASS_DIR."KB_Page.php";
require_once CLASS_DIR."KBGroup.php";
require_once CLASS_DIR."KBPageDataProviderDB.php";
require_once CLASS_DIR."KBPageInfo.php";
require_once CLASS_DIR."KBPageRevision.php";
//require_once CLASS_DIR."Datacore/DataPoint.php";
//require_once CLASS_DIR."Datacore/DataSeries.php";
//require_once CLASS_DIR."Datacore/Study.php";
//require_once CLASS_DIR."Datacore/Plotter.php";
//require_once CLASS_DIR."Game/Player.php";
require_once CLASS_DIR."EditorJSDocument.php";
require_once CLASS_DIR."EVA.php";
require_once CLASS_DIR."CalendarScheduler.php";
require_once CLASS_DIR."CalendarEvent.php";
require_once CLASS_DIR."RecurringEvent.php";
require_once CLASS_DIR."File.php";
require_once CLASS_DIR."HTTPHeaders.php";
require_once CLASS_DIR."Tag.php";
require_once CLASS_DIR."Document.php";
require_once CLASS_DIR."JobScheduler.php";
require_once CLASS_DIR."MusicTrack.php";
require_once CLASS_DIR."Chip.php";

require_once CLASS_DIR."Picture.php";
require_once CLASS_DIR."PictureSet.php";
require_once CLASS_DIR."PictureIngest.php";
header("Content-Security-Policy:  frame-ancestors 'self' ".BASE_URI);
ini_set("session.cache_limiter","");
ini_set('xdebug.var_display_max_depth', 10);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);
session_start();
EngineCore::$CurrentUser=User::GetCurrentUser();
$_PAGE_SIDEBAR=Array();
require_once CLASS_DIR."Router.php";
EngineCore::StartLap();
Router::Dispatch();

EngineCore::Write2Debug("<strong>Route:</strong>".EngineCore::GET("route"));


//sidebar
$aerr="";
if(isset($_SESSION['autherror']))
{
	$aerr=",aerr=Incorrect username/password";
	unset($_SESSION['autherror']);
}
EngineCore::AddSideBar("&nbsp;", (new TemplateProcessor("membercard".$aerr))->process(true),"/userpanel");



//any output only below this line

    

$data=EngineCore::RenderPage();
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$finish = $time;
$total_time = round(($finish - $start), 4);
echo str_replace("||||generatedtime||||",$total_time,$data);
//$tpl->dump();