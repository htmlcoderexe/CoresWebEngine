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
    
    public const TYPE_ORG=0;
    public const TYPE_FUNC=1;
    public const TYPE_ROLE=2;
    public const TYPE_SPECIAL=3;
    
    public function FromId($id)
    {
        $g=DBHelper::RunRow(DBHelper::Select("user_groups", ["id","type","name","description","owner"],['id'=>$id]),[$id]);
        if($g)
        {
            $this->FromRow($g);
        }
    }
    
    public function FromRow($row)
    {
        $this->type=$row['type'];
        $this->id=$row['id'];
        $this->description=$row['description'];
        $this->name=$row['name'];
        
        $this->owner=new User(User::GetUsername($row['owner']));
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
}
