<?php

class User
{

    public $userid;
    public $username;
    public $permissions;
    public $basicinfo;

    function __construct($username)
    {
        $this->username = $username;
        $this->userid = User::GetId($username);
        $this->permissions = null;
        if($this->userid == 0)
        {
            
        }
        if($this->userid == -1)
        {
            EngineCore::Write2Debug("Warning: invalid user '$username'");
        }
    }

    //gets
    public static function GetId($username)
    {
        if($username == "Guest")
        {
            return 0;
        }
        $user = DBHelper::RunScalar("SELECT id FROM users WHERE username = ?",[$username],0);
        if($user === false)
        {
            return -1;
        }
        return $user;
    }

    public static function GetUsername($userid)
    {
        $user = DBHelper::RunScalar("SELECT username FROM users WHERE id = ?",[$userid],0);
        if($user === false)
        {
            return "Guest";
        }
        return $user;
    }

    public static function GetCurrentUser()
    {
        if(!isset($_SESSION['userid']))
        {
            return new User("Guest");
        }
        $username = User::GetUsername($_SESSION['userid']);
        return new User($username);
    }

    public function IsGuest()
    {
        return ($this->userid == 0 || $this->username == "Guest");
    }

    public function GetAllProperties()
    {
        $prop = DBHelper::RunTable("
		SELECT property_display_name,user_property_value
		FROM user_property_map
		INNER JOIN user_properties
		ON user_property_map.user_property_id = user_properties.id
		WHERE user_id=?
		",[$this->userid]);
        echo EngineCore::VarDumpString($prop);
    }

    public function GetBasicInfo()
    {
        if($this->basicinfo)
        {
            return $this->basicinfo;
        }
        $querystring = "SELECT  `display_name`, `title`, `mail_address`, `location_id`, `partition_id`, `dob`, sex "
                . "FROM `userinfo` WHERE `user_id`=?";
        $info = DBHelper::RunRow($querystring, [$this->userid]);
        $this->basicinfo = $info;
        if(!$this->basicinfo)
        {
            $this->basicinfo = Array();
        }
        return $this->basicinfo;
    }

    public function GetAge()
    {
        $bi = $this->GetBasicInfo();
        return ((int) date("Y") - (int) $bi['dob']);
    }

    //end getters
    //auth

    /**
     * Try to login the user with the supplied credentials
     * @param type $username Username
     * @param type $password Password
     * @param type $ignoredisabled
     * @return bool Whether the login succeeded or failed
     * 
     */
    public static function LogIn($username, $password, $ignoredisabled = false)
    {
        // try to get the user from the username
        $user = new User($username);
        // invalid user - no good, return false and set autherror
        if($user->userid <= 0)
        {
            $_SESSION['autherror'] = true;
            return false;
        }
        // ask the db for any users matching this UID
        $querystring = "SELECT id,username,passwordhash,disabled FROM users WHERE id=?";
        $authline = DBHelper::RunTable($querystring,[$user->userid]);
        
        //var_dump($authline);die;
        // if anything but exactly 1 result is returned, no good 
        if(count($authline) !== 1)
        {
            $_SESSION['autherror'] = true;
            return false;
        }
        // get the user info and check
        $userinfo = $authline[0];
        // if user is disabled and we care about that, no good
        if(($userinfo['disabled'] == 1) && (!$ignoredisabled))
        {
            $_SESSION['autherror'] = true;
            return false;
        }
        // check the password
        $passwordok = password_verify($password, $userinfo['passwordhash']);
        // if all good, save the userid to session, unset the error and return success
        if($passwordok)
        {
            $_SESSION['userid'] = $user->userid;
            unset($_SESSION['autherror']);
            return true;
        }
        // no clue how we got there, best to fail just to be sure
        $_SESSION['autherror'] = true;
        return false;
    }
    /**
     * Logs the user out
     */
    public static function LogOut()
    {
        // it's that simple!
        unset($_SESSION['userid']);
    }

    //end auth
    //Permissions
    
    /**
     * Gets the ID of a permission with a specific name
     * @param string $name Name of the permission
     * @return int ID of the permission if found, -1 otherwise.
     */
    public static function GetPermissionID($name): int
    {
        $perm_id = DBHelper::RunScalar("SELECT id FROM permissions WHERE name = ?",[$name],0);
        if($perm_id === false)
        {
            return -1;
        }
    }
    
    
    /**
     * Get the user's permissions
     * @return array A list of permissions of the user
     */
    public function GetPermissions()
    {
        // if this was already loaded before, just return that
        if($this->permissions != null)
        {
            return $this->permissions;
        }
        $permissions = Array();
        // guests have no permissions
        if($this->IsGuest())
        {
            return $permissions;
        }
        // ask the db for any permissions matching this userid
        $permrequest = DBHelper::RunList(DBHelper::Select("user_permissions",["permission_name"],["user_id"=>$this->userid]),[$this->userid]);
        // return none
        if(count($permrequest)<1)
        {
            return[];
        }
        // go through each row and add the permission to the list
        foreach($permrequest as $perm)
        {
            $permissions[] = $perm;
        }
        // now get all the group permissions
        $groups=UserGroup::GetUserGroups($this->userid);
        foreach($groups as $group)
        {
            $perms=UserGroup::GetPermissionsByGID(intval($group));
            foreach($perms as $perm)
            {
                $permissions[]=$perm;
            }
        }
        // save for next time
        $this->permissions = $permissions;
        return $this->permissions;
    }
    /**
     * Check if this user has a specific permission
     * @param type $permission name of the permission
     * @return bool
     */
    public function HasPermission($permission)
    {
        // guests DO NOT get permissions
        if($this->IsGuest())
        {
            return false;
        }
        $yes=in_array("super", $this->GetPermissions()) ? true : in_array($permission, $this->GetPermissions());
        EngineCore::Dump2Debug($this);
        // check if user has the super permission, if not, check if requested permission is in user's permissions
        return $yes;
    }
    
    public function CanManagePermission($permission)
    {
        if($this->HasPermission("permission.super"))
        {
            return true;
        }
        $permgroup_all=UserGroup::FromName($permission.".all");
        if($permgroup_all && $permgroup_all->HasMember($this->userid))
        {
            return true;
        }
    }
    
    public function CanGrantPermission($permission)
    {
        if($this->CanManagePermission($permission))
        {
            return true;
        }
        if($this->HasPermission("permission.grant"))
        {
            return true;
        }
        $permgroup_grant=UserGroup::FromName($permission.".grant");
        if($permgroup_grant && $permgroup_grant->HasMember($this->userid))
        {
            return true;
        }
        return false;
    }
    public function CanRevokePermission($permission)
    {
        if($this->CanManagePermission($permission))
        {
            return true;
        }
        if($this->HasPermission("permission.revoke"))
        {
            return true;
        }
        $permgroup_revoke=UserGroup::FromName($permission.".revoke");
        if($permgroup_revoke && $permgroup_revoke->HasMember($this->userid))
        {
            return true;
        }
        return false;
    }
    
    /**
     * Grant a permission
     * @param type $permission the name of the permission to grant
     * @return type True if permission was granted, else false
     */
    public function GrantPermission($permission)
    {
        // nothing to do, confirm existing permission
        if($this->HasPermission($permission))
        {
            return true;
        }
        // guests absolutely do not get permissions, ever
        if($this->IsGuest())
        {
            return false;
        }
        // ask the db if such a permission even exists
        $perm_id = self::GetPermissionID($permission);
        if($perm_id === -1)
        {
            return false;
        }
        // write the permission
        DBHelper::Insert("userpermissionmap", [$this->user,$perm_id]);
    }

    public function RevokePermission($permission)
    {
        if($this->IsGuest())
        {
            return false;
        }
        $perm_id = self::GetPermissionID($permission);
        if($perm_id===-1)
        {
            return false;
        }
        DBHelper::Delete("userpermissionmap", ['user_id'=>$this->user,'permission_id'=>$perm_id]);
    }

    public static function Create($username, $password, $nickname, $email)
    {
        $user = new User($username);
        if($user->userid>0)
        {
            EngineCore::WriteUserError("Username already exists.","usermgr");
            return null;
        }
        DBHelper::Insert('users', [null, $username, password_hash($password, PASSWORD_DEFAULT), time(), 1, 0]);
        DBHelper::Insert('userinfo', [null, $nickname, 1, $email, 1, 1, 0, 0]);
        $user = new User($username);
        return $user;
    }

    public function Enable()
    {
        DBHelper::Update("users",['disabled'=>0],['id'=>$this->userid]);
    }

    public function Disable()
    {
        DBHelper::Update("users",['disabled'=>1],['id'=>$this->userid]);
    }

    //end permissions
}
