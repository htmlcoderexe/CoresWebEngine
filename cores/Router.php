<?php
namespace Cores;

class Router
{
    public static $RouteMap = [];
    public static $RoutePermissions = [];
    public static $PostRouteMap = [];
    public static $PostRoutePermissions = [];
    public const DEFAULT_ROUTE = "main";
    public const DEFAULT_ACTION = "default";
    public const METHOD_GET = 0;
    public const METHOD_POST = 1;
    public static function Dispatch()
    {
        //  URL rewriting converts requests of the form "(example.net)/path/to/something"
        //  to "(example.net)/index.php?route=path/to/something"
        // "route" gets populated with the path component
        $query=EngineCore::GET("route");
        
        //split route into individual segments
        $pieces_pre=$query==''?[]:explode("/",$query);
        $pieces = [];
        foreach($pieces_pre as $piece)
        {
            if($piece!="")
            {
                $pieces[]=$piece;
            }
        }
        if(count($pieces)<1)
        {
            $pieces[]=self::DEFAULT_ROUTE;
        }
        if(count($pieces)<2)
        {
            $pieces[]=self::DEFAULT_ACTION;
        }
        
        $mapzoom = &self::$RouteMap;
        
        if(EngineCore::IsPOST())
        {
            $mapzoom = &self::$PostRouteMap;
        }
        
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
                    $routeperms = self::$RoutePermissions;
                    if(EngineCore::IsPOST())
                    {
                        $routeperms = self::$PostRoutePermissions;
                    }
                    if(!EngineCore::$CurrentUser->HasPermission($routeperms[$route]))
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
        return self::AddPostOrGetRoute($route, $func, self::METHOD_GET, $perms);
    }
    public static function AddPostRoute(string $route, callable $func, string $perms = '')
    {
        return self::AddPostOrGetRoute($route, $func, self::METHOD_POST, $perms);
    }
    
    public static function AddPostOrGetRoute(string $route, callable $func, int $method = self::METHOD_GET, string $perms = '')
    {
        $pieces = explode(separator: "/", string: $route);
        if(count($pieces)===1)
        {
            $pieces[]='default';
            $route.="/default";
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
        if($method === self::METHOD_POST)
        {
            $map = &self::$PostRouteMap;
        }
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
        switch($method)
        {
            case self::METHOD_GET:
            {
                self::$RoutePermissions[$route] = $perms;
                break;
            }
            case self::METHOD_POST:
            {
                self::$PostRoutePermissions[$route] = $perms;
                break;
            }    
        }
        return true;
    }
}
