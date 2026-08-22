<?php
namespace Controllers;

use Common\DBHelper;
use Cores\EngineCore;
use Models\User\User;
use Models\User\UserGroup;
use PostRoute;
use Route;

/**
 * Description of ControlPanelGroupsManager
 *
 */
class ControlPanelGroupsManager
{
    #[Route('cpanel/groups/list','user.groups.list')]
    public static function ListUserGroups()
    {
        $groups=DBHelper::RunTable(DBHelper::Select("user_groups", ["id","type","name","description","owner"], []),[]);
        for($i=0;$i<count($groups);$i++)
        {
            $g = UserGroup::FromRow($groups[$i]);
            $members=$g->GetMembers();
            $groups[$i]['count']=count($members);

        }
        return ['entity_type'=>'group/group.list','groups'=>$groups];
    }
    #[Route('cpanel/groups/view','user.groups.view')]
    public static function ShowGroup($id =0)
    {
        
    }
    
    #[Route('cpanel/groups/create','user.groups.manage')]
    public static function ShowGroupCreator()
    {
        return ['entity_type'=>'group/group.edit',
            'types'=>UserGroup::TYPES];
    }
    #[Route('cpanel/groups/edit','user.groups.manage')]
    public static function ShowGroupEditor($gid = 0)
    {
        $groupinfo=UserGroup::FromId($gid);
        if(!$groupinfo)
        {
            return EngineCore::Error(404, "Group does not exist.");
        }
        $memberlist=$groupinfo->GetMembers();
        $members=[];
        foreach($memberlist as $mid)
        {
            $member=['uid'=>$mid];
            $member['username']=User::GetUsername($mid);
            $members[]=$member;
        }
        $e = (array)$groupinfo;
        $e['members']=$members;
        $e['adduser']="true";
        $e['gid']=$gid;
        $e['entity_type'] = "group/group.edit";
        $e['types']=UserGroup::TYPES;
        return $e;
    }
    #[PostRoute('cpanel/groups/save','user.groups.manage')]
    public static function CreateOrUpdateGroup()
    {
        $gid = intval(EngineCore::POST('gid',-1));
        $gname = EngineCore::POST("gname");
        $gdesc = EngineCore::POST("gdesc");
        $gtype = EngineCore::POST("gtype");
        $gowner = EngineCore::POST("ownerid");
        if($gid==-1)
        {
            $group = UserGroup::Create($gname,$gdesc,$gtype);
            if($group)
            {
                EngineCore::GTFO("/cpanel/groups/edit/".$group->id);
            }
            else
            {
                EngineCore::GTFO("/cpanel/groups/list");
            }
        }
        if(!$gname)
        {
            EngineCore::GTFO("/cpanel/groups/list");
        }
        $group = UserGroup::FromId($gid);
        if(!$group)
        {
            return EngineCore::Error(404, "User group does not exist");
        }
        $group->name = $gname;
        $group->description= $gdesc;
        $group->type = $gtype;
        $group->owner = $gowner;
        $group->Save();
        EngineCore::GTFO("/cpanel/groups/edit/".$group->id);
    }
    
    #[PostRoute('cpanel/groups/adduser','user.groups.manage')]
    public static function AddUser()
    {
        $gid=EngineCore::POST('gid');
        $username=EngineCore::POST('username');
        $uid=User::GetId($username);
        if($gid!=="" && $username !== "" && $uid > 0)
        {
            $group=UserGroup::FromId($gid);
            if(!$group)
            {
                return EngineCore::Error(404, "User group does not exist,");
            }
            if(!$group->UserCanEditGroup(EngineCore::$CurrentUser))
            {
                EngineCore::WriteUserError("You aren't allowed to do this.", "permission");
                return EngineCore::Error(403, "User does not have edit privilege on the group.");
            }
            $group->AddMember($uid);
            
        }
        else
        {
            EngineCore::WriteUserError("Bad username.","grouppmgr");
        }
        EngineCore::GTFO("/cpanel/groups/edit/".$gid);
    }
    #[PostRoute('cpanel/groups/removeuser','user.groups.manage')]
    public static function RemoveUser()
    {
        $gid=EngineCore::POST('gid');
        $username=EngineCore::POST('username');
        $uid=User::GetId($username);
        if($gid!=="" && $username !== "" && $uid > 0)
        {
            $group=UserGroup::FromId($gid);
            if(!$group)
            {
                return EngineCore::Error(404, "User group does not exist,");
            }
            if(!$group->UserCanEditGroup(EngineCore::$CurrentUser))
            {
                EngineCore::WriteUserError("You aren't allowed to do this.", "permission");
                return EngineCore::Error(403, "User does not have edit privilege on the group.");
            }
            if(count($group->GetMembers())<2 && $uid === intval(EngineCore::$CurrentUser->userid))
            {
                EngineCore::WriteUserError("Cannot leave group empty.","groupmgr");
            }
            else
            {
                $group->RemoveMember($uid);
            }
        }
        else
        {
            EngineCore::WriteUserError("Bad username.","grouppmgr");
        }
        EngineCore::GTFO("/cpanel/groups/edit/".$gid);
    }
    
    
}
