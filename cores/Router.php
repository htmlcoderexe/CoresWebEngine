<?php
namespace Cores;

class Router
{
    public static $RouteMap = [];
    public const DEFAULT_ROUTE = "main";
    public const DEFAULT_ACTION = "default";
    public static function Dispatch()
    {
        //  URL rewriting converts requests of the form "(example.net)/path/to/something"
        //  to "(example.net)/index.php?route=path/to/something"
        // "route" gets populated with the path component
        $query=EngineCore::GET("route");
        //split route into individual segments
        $pieces=$query==''?[]:explode("/",$query);
        
        if(count($pieces)<1)
        {
            $pieces[]=self::DEFAULT_ROUTE;
        }
        if(count($pieces)<2)
        {
            $pieces[]=self::DEFAULT_ACTION;
        }
        $mapzoom = &self::$RouteMap;
        while(count($pieces)>0)
        {
            $p = array_shift($pieces);
            if(isset($mapzoom[$p]))
            {
                if(is_callable($mapzoom[$p]))
                {
                   
                   return $mapzoom[$p](...$pieces);
                }
                else
                {
                    $mapzoom = &$mapzoom[$p];
                    continue;
                }
                
            }
            //404
            return false;
        }
        
        //first segment should be module name, shift it off
        $modulename=count($pieces)>0?array_shift($pieces):"main"; //munch, munch
        //second segment is action, shift it off
        $action=count($pieces)>0?array_shift($pieces):"default"; //om nom nom
        //default route
        if($modulename == "")
        {
            $modulename = Router::DEFAULT_ROUTE;
        }
        // default action
        if($action == "")
        {
            $action = Router::DEFAULT_ACTION;
        }
        $module=new Module($modulename);
        //hand the rest of segments as arguments to module's action. This can be empty
        $module->PerformAction($action,$pieces); //CHOMP!!
    }
    
    public static function AddRoute(string $route, callable $func)
    {
        $pieces = explode(separator: "/", string: $route);
        if(count($pieces)===1)
        {
            $pieces[]='default';
        }
        // at least 2 pieces
        // for 3 stage route like /ticket/group/view
        // should be in map['ticket']['group']['view'] -> callable
        /*
         * [
         *      'main'=>[
         *          'default'=>callable, 
         *          'test'=>callable
         *      ],
         *      'ticket'=>[
         *          'view'=>callable, 
         *          'new'=>callable, 
         *          'group'=>[
         *              'create'=>callable,
         *              'list'=>callable
         *          ]
         *      ]
         */
        
        //*/
        $map = &self::$RouteMap;
        $level = 0;
        for($i=0;$i<count($pieces)-1;$i++)
        {
            if(!isset($map[$pieces[$i]]))
            {
                $map[$pieces[$i]] = [];
            }
            if(is_callable($map[$pieces[$i]]))
            {
                // failed
                return false;
            }
            $map = &$map[$pieces[$i]];
        }
        if(isset($map[$pieces[count($pieces)-1]]))
        {
            return false;
        }
        $map[$pieces[count($pieces)-1]] = $func;
        return true;
    }
}
