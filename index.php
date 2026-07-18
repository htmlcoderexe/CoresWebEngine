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
//EngineCore::$DEBUG = true;
ini_set("display_errors", "1");
error_reporting(E_ALL & ~E_NOTICE);
setlocale(LC_CTYPE, "en_US.UTF-8");

global $_PAGE_CONTENT;
global $_PAGE_SIDEBAR;
global $_PAGE_TITLE;
global $_DEBUG_INFO;
global $_PAGE_STYLESHEETS;
global $_PAGE_SCRIPTS;

require_once CLASS_DIR."DBHelper.php";
require_once CLASS_DIR."TemplateProcessor.php";
require_once CLASS_DIR."User/UserExtendedProps.php";
require_once CLASS_DIR."EVA.php";
spl_autoload_register(function ($class) {
    require_once CLASS_DIR. $class . '.php';
});
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
$time = microtime();
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

    

$time = microtime();
$data=EngineCore::RenderPage();
//$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$finish = $time;
$total_time = round(($finish - $start), 4);
echo str_replace("||||generatedtime||||",$total_time,$data);
//$tpl->dump();