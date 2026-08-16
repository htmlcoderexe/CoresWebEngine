<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Controllers;
use \Route;
use \Cores\Router;

/**
 * Description of DebugInstrumentation
 *
 * @author admin
 */
class DebugInstrumentation
{
    #[Route('debug/routemaps','super')]
    public static function DumpRouteMaps()
    {
        var_dump(Router::$RouteMap);
        var_dump(Router::$PostRouteMap);
        var_dump(Router::$RoutePermissions);
        var_dump(Router::$PostRoutePermissions);
        die;
    }
}
