<?php

namespace Controllers;

use Common\DBHelper;
use Cores\EngineCore;
use Models\User\User;
use PostRoute;
use Route;

/**
 * Description of ControlPanelUsersManager
 *
 */
class ControlPanelUsersManager
{
    #[Route('cpanel/users/list','cpanel.users.list')]
    public static function ListUsers()
    {
        return ['entity_type'=>'user/user.list',
            'users'=>DBHelper::RunTable(DBHelper::Select("users", ["id","username","timestamp","disabled"], []),[])
        ];
    }
    
    #[Route('cpanel/users/create','cpanel.users.manage')]
    public static function ShowUserForm()
    {
        
    }
    
    #[PostRoute('cpanel/users/create','cpanel.users.manage')]
    public static function CreateUser()
    {
        $username=EngineCore::POST("username");
        $password=EngineCore::POST("password");
        $email="nobody@example.net";
        $nickname=$username;
        $newuser=User::Create($username,$password,$nickname,$email);
        EngineCore::GTFO("/cpanel/users/list");
    }
}
