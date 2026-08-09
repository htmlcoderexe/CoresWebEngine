<?php
namespace Controllers;

use Models\User\User;
use Cores\EngineCore;
use \Route as Route;

/**
 * Provides routes for logging in/out and registering
 *
 * @author admin
 */
class AuthController
{
    #[Route('login')]
    public static function UserLogIn()
    {
        User::LogIn(EngineCore::POST('username'),EngineCore::POST('password'));
	EngineCore::FromWhenceYouCame();
	die();
    }
    #[Route('logout')]
    public static function UserLogOut()
    {
        User::LogOut();
	EngineCore::FromWhenceYouCame();
	die();
    }
}
