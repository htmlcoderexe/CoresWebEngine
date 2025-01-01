<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of UserGroup
 *
 * @author admin
 */
class UserGroup
{
    
    private $memberlist;
    public int $id;
    public User $owner;
    public string $name;
    public int $type;
    public string $description;
    
    public const TYPE_ORG=0;
    public const TYPE_FUNC=1;
    public const TYPE_ROLE=2;
    public const TYPE_SPECIAL=3;
    
    public const TYPES= [
        ["name"=>"Organisation", "code"=>0],
        ["name"=>"Functional", "code"=>1],
        ["name"=>"Role", "code"=>2],
        ["name"=>"Special", "code"=>3]
    ];
    
    public static function FromId($id)
    {
        $g=DBHelper::RunRow(DBHelper::Select("user_groups", ["id","type","name","description","owner"],['id'=>$id]),[$id]);
        if($g)
        {
            return self::FromRow($g);
        }
    }
    
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
    
    public function Save()
    {
        DBHelper::Update("user_groups",["type"=>$this->type,"name"=>$this->name,"description"=>$this->description,"owner"=>$this->owner->userid],['id'=>$this->id]);
    }
    
    public function GetMembers()
    {
        if(!$this->memberlist)
        {
            $this->memberlist=DBHelper::RunList(DBHelper::Select("user_group_memberships",["uid"],["gid"=>$this->id]),[$this->id]);
        }
        return $this->memberlist;
    }
    
    public function UserCanEditGroup(User $user)
    {
        if($this->owner->userid === $user->userid)
        {
            return true;
        }
        if($user->HasPermission("groups.super"))
        {
            return true;
        }
        if($user->HasPermission("groups.edit.".$this->id))
        {
            return true;
        }
        return false;
    }
    
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
    public function RemoveMember($uid)
    {
        $index=array_Search($this->GetMembers(),$uid);
        
        if($index!==false)
        {
            unset($this->memberlist[$index]);
            $this->memberlist=array_values($this->memberlist);
            return true;
        }
        return false;
    }
}
