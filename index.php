<?php

// TODO make this detect the missing file and do a first run wizard thing

// consult the file "config.example.php" for what is needed
require_once "config.php"; 



spl_autoload_register(function ($class) {
    
    $mapping = [
        'Models'=>'models',
        'ViewModels'=>'viewmodels',
        'Common'=>'lib',
        'Cores'=>'cores'
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
        //throw new Exception("Unable to load [$class] from [$fullpath]");
    }
});


use Models\User\User as User;
use Cores\EngineCore as EngineCore;
use Cores\Router as Router;
use Cores\TemplateProcessor as TemplateProcessor;

function emit_result($result)
{
    $viewname = $result['entity_type'];
    $accepts = Common\HTTPHeaders::GetAccepts($_SERVER['HTTP_ACCEPT']);
    $mime = $accepts[0]['mime'];
    if(isset($result['error']))
    {
        Common\HTTPHeaders::Status($result['error']);
    }
    switch($mime)
    {
        case "text/html":
        {
            $tpl = new Cores\TemplateProcessor($viewname,false,'views/');
            $tpl->tokens = $result;
            EngineCore::SetPageContent($tpl->process(true));
            break;
        }
        case "application/json":
        {
            if(isset($result['error']))
            {

                if($result['message']=='')
                {
                    $result['message'] = Common\HTTPHeaders::Statuses[$result['error']]??'Uknown error';
                }
                if($result['title']=='')
                {
                    $result['title'] = Common\HTTPHeaders::Statuses[$result['error']]??'Uknown error';
                }
            }
            EngineCore::EmitJSON($result);
            break;
        }
    }
}



$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;
global $_DEBUG;
$_DEBUG=true;
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

require_once "cores/TemplateProcessor.php";
require_once "cores/Router.php";
require_once "cores/EngineCore.php";
require_once "lib/DBHelper.php";
require_once "lib/EVA.php";
require_once "lib/CSRF.php";
require_once "models/User/UserExtendedProps.php";




ini_set("session.cache_limiter","");
ini_set('xdebug.var_display_max_depth', 10);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);
   session_start();
 
if(!isset($_SESSION['secret_id']))
{
    \Common\CSRF::SetupSecret();
}

\Common\CSRF::SetupToken();

header("Content-Security-Policy:  frame-ancestors 'self' ".BASE_URI);
//header("X-CSRF: ".\Common\CSRF::$token);

$result = "";
if(EngineCore::IsPOST() && !\Common\CSRF::VerifyToken())
{
$result = EngineCore::Error(code: 403, message: "CSRF error.");
}

EngineCore::$CurrentUser=User::GetCurrentUser();

$_PAGE_SIDEBAR=Array();

#[\Attribute]
class Route
{
    public function __construct(public string $path, public string $perms = ''){}
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
            Router::AddRoute($r->path, $func->getClosure(), $r->perms);
        }
    }
}
//die;
EngineCore::StartLap();

$time = microtime();
if(!$result)
{
    $result = Router::Dispatch();
    if(!$result)
    {
        $result = EngineCore::Error(404);
    }
}
emit_result($result);


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