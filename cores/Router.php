<?php
namespace Cores;

class Router
{
    public static $RouteMap = [];
    public static $RoutePermissions = [];
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
        $route_pieces = [];
        while(count($pieces)>0)
        {
            $p = array_shift($pieces);
            $route_pieces[]=$p;
            if(isset($mapzoom[$p]))
            {
                if(is_callable($mapzoom[$p]))
                {
                    $route = implode(separator: "/", array:  $route_pieces);
                    if(!EngineCore::$CurrentUser->HasPermission(self::$RoutePermissions[$route]))
                    {
                        return EngineCore::Error(403);
                    }
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
    }
    
    public static function AddRoute(string $route, callable $func, string $perms = '')
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
        self::$RoutePermissions[$route] = $perms;
        return true;
    }
}
