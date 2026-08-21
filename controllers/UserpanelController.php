<?php
namespace Controllers;

use Cores\EngineCore;
use Models\User\User;
use Models\User\UserExtendedProps;
use PostRoute;
use Route;

class UserpanelController
{
    #[Route('user/view','loggedin')]
    public static function DisplayUser($userid="")
    {
        $cu=User::GetCurrentUser();
        $username = User::GetUsername(intval($userid));
        
        if(!$username)
        {
            return EngineCore::Error(404,"User does not exist");
        }
        $user = new User($username);
        $e = ['entity_type'=>'user/user'];
        if($user->userid==$cu->userid)
        {
                $user=$cu;
                $e['self']='true';
        }
        $e['nickname']=UserExtendedProps::GetOneProperty($user,'nickname');
        $e['firstname']=UserExtendedProps::GetOneProperty($user,'firsname');
        $e['lastname']=UserExtendedProps::GetOneProperty($user,'lastname');
        $e['username']=$user->username;
        $e['id']=$user->userid;
        
        return $e;
    }
    #[Route('userpanel/edit','loggedin')]
    public static function ShowUserEditor()
    {
        $cu=User::GetCurrentUser();
        
        return ['entity_type'=>'user/user.edit'];
    }
    #[PostRoute('userpanel/property','loggedin')]
    public static function SetProp()
    {
        $property=EngineCore::POST('property','____invalid');
	$value=EngineCore::POST('value','____invalid');
	$user=User::GetCurrentUser();
	UserExtendedProps::SetOneProperty($user,$property,$value);
	echo UserExtendedProps::GetOneProperty($user,$property);
	die;
    }
}