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
    
    $mapping = [
        'Models'=>'models',
        'ViewModels'=>'viewmodels'
    ];
    if(file_exists(CLASS_DIR. $class . '.php'))
    {
        require_once CLASS_DIR. $class . '.php';
        return;
    }
    
    $path = explode(string: $class, separator: "\\");
    if($path[0]=="")
    {
        array_shift($path);
    }
    $type = array_shift($path);
    $prefix = "";
    if(isset($mapping[$type]))
    {
        $prefix = $mapping[$type];
    }
    $fullpath = $prefix.DIRECTORY_SEPARATOR.implode(array: $path, separator: DIRECTORY_SEPARATOR).".php";
    if(file_exists($fullpath))
    {
        require_once($fullpath);
    }
    else
    {
        throw new Exception("Unable to load [$class] from [$fullpath]");
    }
});
header("Content-Security-Policy:  frame-ancestors 'self' ".BASE_URI);
ini_set("session.cache_limiter","");
ini_set('xdebug.var_display_max_depth', 10);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);
session_start();
EngineCore::$CurrentUser=\User::GetCurrentUser();
$_PAGE_SIDEBAR=Array();
require_once CLASS_DIR."Router.php";

#[\Attribute]
class Route
{
    public function __construct(public string $path){}
}
#[\Attribute]
class View
{
    public function __construct(public string $name){}
}


foreach(glob("controllers/*.php") as $filename)
{
    require_once $filename;
    $classname = substr(string: $filename,offset: 12,length:-4);
    $ref = new \ReflectionClass("Controllers\\".$classname);
    $funcs = $ref->getMethods();
    foreach($funcs as $func)
    {
        $routes = $func->getAttributes("Route");
        foreach($routes as $route)
        {
            $r = $route->newInstance();
            Router::AddRoute($r->path, $func->getClosure());
        }
    }
}
//die;
EngineCore::StartLap();
$time = microtime();
$result = Router::Dispatch();
if(!$result)
{
    die("404");
}

$viewname = $result['entity_type'];
$accepts = \HTTPHeaders::GetAccepts($_SERVER['HTTP_ACCEPT']);
$mime = $accepts[0]['mime'];
switch($mime)
{
    case "text/html":
    {
        $tpl = new TemplateProcessor($viewname,false,'views/');
        $tpl->tokens = $result;
        EngineCore::SetPageContent($tpl->process(true));
        break;
    }
    case "application/json":
    {
        EngineCore::EmitJSON($result);
        break;
    }
}


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