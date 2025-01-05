<?php
/**
 * Represents a group of users
 *
 * @author htmlcoderexe
 */
class UserGroup
{
    
    private $memberlist;
    private $permissions;
    /**
     * Group ID
     * @var int
     */
    public int $id;
    /**
     * Group owner
     * @var User
     */
    public User $owner;
    /**
     * Group name
     * @var string
     */
    public string $name;
    /**
     * Group type, see types further down
     * @var int
     */
    public int $type;
    /**
     * Group description for management convenience
     * @var string
     */
    public string $description;
    
    public const TYPE_ORG=0;
    public const TYPE_FUNC=1;
    public const TYPE_ROLE=2;
    public const TYPE_SPECIAL=3;
    /**
     * This standardises group types and is also used to generate the 
     * HTML for the control that allows selecting/displaying the group's
     * type.
     */
    public const TYPES= [
        ["name"=>"Organisation", "code"=>0],
        ["name"=>"Functional", "code"=>1],
        ["name"=>"Role", "code"=>2],
        ["name"=>"Special", "code"=>3]
    ];
    
    /**
     * Load a group from database, given its ID.
     * @param int $id Group ID
     * @return \UserGroup Group if exists
     */
    public static function FromId($id)
    {
        $g=DBHelper::RunRow(DBHelper::Select("user_groups", ["id","type","name","description","owner"],['id'=>$id]),[$id]);
        if($g)
        {
            return self::FromRow($g);
        }
    }
    
    /**
     * Load a group from an associative array (as received from the database)
     * @param array $row Row containing group data
     * @return \UserGroup Group object from the data provided
     */
    public static function FromRow($row)
    {
        $group=new UserGroup();
        $group->type=$row['type'];
        $group->id=$row['id'];
        $group->description=$row['description'];
        $group->name=$row['name'];
        
        $group->owner=new User(User::GetUsername($row['owner']));
    
        return $group;
    }
    
    /**
     * Finds all groups a given User is a member of
     * @param int $uid UserID to check
     * @return array List of group IDs for this user
     */
    public static function GetUserGroups($uid)
    {
        return DBHelper::RunList(DBHelper::Select("user_group_memberships",["uid"],["uid"=>$uid]),[$uid]);
    }
    
    /**
     * Writes group's basic data (excluding members) to database.
     */
    public function Save()
    {
        DBHelper::Update("user_groups",["type"=>$this->type,"name"=>$this->name,"description"=>$this->description,"owner"=>$this->owner->userid],['id'=>$this->id]);
    }
    
    /**
     * Get a list of this group's members.
     * @return array List of group's members
     */
    public function GetMembers()
    {
        if(!$this->memberlist)
        {
            $this->memberlist=DBHelper::RunList(DBHelper::Select("user_group_memberships",["uid"],["gid"=>$this->id]),[$this->id]);
        }
        return $this->memberlist;
    }
    
    /**
     * Check if a given user can modify the group.
     * @param \User $user The user to check
     * @return bool True if user can modify the group, false otherwise.
     */
    public function UserCanEditGroup(User $user)
    {
        // owner is allowed by default
        if($this->owner->userid === $user->userid)
        {
            return true;
        }
        // this permission allows full control over any group
        if($user->HasPermission("groups.super"))
        {
            return true;
        }
        // specific permission given to edit the group
        if($user->HasPermission("groups.edit.".$this->id))
        {
            return true;
        }
        return false;
    }
    
    /**
     * Adds a specific user to the group
     * @param int $uid UserID of the member to be added
     * @return bool True if member was added, false if not (see user messages why)
     */
    public function AddMember($uid)
    {
        
        if(in_array($uid,$this->GetMembers()))
        {
            return true;
        }
        $this->memberlist[]=$uid;
        DBHelper::Insert("user_group_memberships",[null,$this->id,$uid]);
        return true;
    }
    
    /**
     * Removes a specific user from the group
     * @param int $uid UserID of the member to be removed
     * @return bool True if member was removed, false otherwise
     */
    public function RemoveMember($uid)
    {
        $index=array_Search($uid,$this->GetMembers());
        
        if($index!==false)
        {
            unset($this->memberlist[$index]);
            $this->memberlist=array_values($this->memberlist);
            DBHelper::Delete("user_group_memberships",['uid'=>$uid,'gid'=>$this->id]);
            return true;
        }
        return false;
    }
    
    /**
     * Gets permissions for a group specified by its ID
     * @param int $gid Group ID to be checked
     * @return array List of permissions granted to the group specified by ID
     */
    public static function GetPermissionsByGID($gid)
    {
        return DBHelper::RunList(DBHelper::Select("group_permissions",["permission_name"],["group_id"=>$gid]),[$gid]);
    }
    
    /**
     * Get the Group's permissions
     * @return array List of permissions granted to this group
     */
    public function GetPermissions()
    {
        // if this was already loaded before, just return that
        if($this->permissions != null)
        {
            return $this->permissions;
        }
        $permissions = Array();
        // ask the db for any permissions matching this gid
        $permrequest = self::GetPermissionsByGID($this->id);
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
        // save for next time
        $this->permissions = $permissions;
        return $this->permissions;
    }
    
}
