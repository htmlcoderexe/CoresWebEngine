<?php

class Router
{
    public static $DefaultRoute="main";
    public static $DefaultAction="default";
    public static function Dispatch()
    {
        //  URL rewriting converts requests of the form "(example.net)/path/to/something"
        //  to "(example.net)/index.php?route=path/to/something"
        // "route" gets populated with the path component
        $query=Utility::GET("route");
        //split route into individual segments
        $pieces=explode("/",$query);//Utility::ddump($pieces);
        //first segment should be module name, shift it off
        $modulename=count($pieces)>0?array_shift($pieces):"main"; //munch, munch
        //second segment is action, shift it off
        $action=count($pieces)>0?array_shift($pieces):"default"; //om nom nom
        //default route
        if($modulename == "")
        {
            $modulename = Router::$DefaultRoute;
        }
        // default action
        if($action == "")
        {
            $action = Router::$DefaultAction;
        }
        $module=new Module($modulename);
        //hand the rest of segments as arguments to module's action. This can be empty
        $module->PerformAction($action,$pieces); //CHOMP!!
    }
}
