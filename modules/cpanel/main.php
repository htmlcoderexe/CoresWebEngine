<?php
use EngineCore as C;


function ModuleFunction_cpanel_ListGroups()
{
    $groups=DBHelper::RunTable(DBHelper::Select("user_groups", ["id","type","name","description","owner"], []),[]);
    for($i=0;$i<count($groups);$i++)
    {
        $g = UserGroup::FromRow($groups[$i]);
        $members=$g->GetMembers();
        $groups[$i]['count']=count($members);
        
    }
    $t=new TemplateProcessor("cpanel/grouplist");
    $t->tokens['groups']=$groups;
    C::AddPageContent($t->process(true));
    
}

function ModuleFunction_cpanel_CreateGroup()
{
    
}

function ModuleFunction_cpanel_AddUser($gid,$uid)
{
    $group=UserGroup::FromId($gid);
    if(!$group)
    {
        C::WriteUserError("Inжalid group", 1);
        return false;
    }
    if(!$group->UserCanEditGroup(C::$CurrentUser))
    {
        C::WriteUserError("You aren't allowed to do this.", 4);
        return false;
    }
    return $group->AddMember($uid);
}
function ModuleFunction_cpanel_ModifyGroup($gid,$groupinfo)
{
    $group=UserGroup::FromId($gid);
    if(!$group)
    {
        C::WriteUserError("Inжalid group", 1);
        return false;
    }
    if(!$group->UserCanEditGroup(C::$CurrentUser))
    {
        C::WriteUserError("You aren't allowed to do this.", 4);
        return false;
    }
    $group->name=$groupinfo['gname'];
    $group->description=$groupinfo['description'];
    $group->type=$groupinfo['gtype'];
    $group->owner=new User(User::GetUsername($groupinfo['ownerid']));
    C::Dump2Debug($group);
    C::Dump2Debug($groupinfo);
    
    $group->Save();
    
    return true;
    
}

function ModuleFunction_cpanel_ShowGroupEditor($gid=0)
{
    $tpl=new TemplateProcessor("cpanel/groupeditor");
        $tpl->tokens['types']=UserGroup::TYPES;
    if($gid>0)
    {
        $groupinfo=UserGroup::FromId($gid);
        $memberlist=$groupinfo->GetMembers();
        $members=[];
        foreach($memberlist as $mid)
        {
            $member=['uid'=>$mid];
            $member['username']=User::GetUsername($mid);
            $members[]=$member;
        }
        $tpl->tokens['type']=$groupinfo->type;
        $tpl->tokens['description']=$groupinfo->description;
        $tpl->tokens['owner']=(array)$groupinfo->owner;
        $tpl->tokens['name']=$groupinfo->name;
        $tpl->tokens['members']=$members;
        $tpl->tokens['adduser']="true";
        $tpl->tokens['gid']=$gid;
        $tpl->tokens['verb']="edit";
    }
    C::AddPageContent($tpl->process(true));
    return;
}




function ModuleAction_cpanel_group($params)
{
    $action=$params[0]??"list";
    switch($action)
    {
        default:
        {
            C::RequirePermission("groups.list");
            ModuleFunction_cpanel_ListGroups();
            
            break;
        }
        case "create":
        {
            C::RequirePermission("groups.create");
            if(C::POST("gname")!="")
            {
                ModuleFunction_cpanel_CreateGroup();
            }
            else
            {
                ModuleFunction_cpanel_ShowGroupEditor();
            }
            break;
        }
        case "edit":
        {
            $gid=intval(C::POST('gid'));
            if(C::POST("gname")!="" && $gid>0)
            {
                $groupinfo=[
                  "gname"=>C::POST('gname'),
                  "gtype"=>C::POST('gtype'),
                  "description"=>C::POST('gdesc'),
                  "ownerid"=>C::POST('ownerid'),  
                ];
                $result=ModuleFunction_cpanel_ModifyGroup($gid,$groupinfo);
                if($result)
                {
                    C::GTFO("/cpanel/group/view/".$gid);
                }
                else
                {
                    C::WriteUserError("Could not update group info.");
                }
            }
            else
            {
                ModuleFunction_cpanel_ShowGroupEditor();
            }
            break;
        }
        case "adduser":
        {
            $gid=C::POST('gid');
            $username=C::POST('username');
            $uid=User::GetId($username);
            if($gid!=="" && $username !== "" && $uid > 0)
            {
                $result=ModuleFunction_cpanel_AddUser($gid,$uid); 
                if($result)
                {
                    C::GTFO("/cpanel/group/view/".$gid);
                }
                else
                {
                    C::WriteUserError("Could not add member.");
                }
                   
            }
            break;
        }
        case "removeuser":
        {
            break;
        }
        case "view":
        {
            $gid=$params[1];
            ModuleFunction_cpanel_ShowGroupEditor($gid);
            
            break;
        }
        case "chown":
        {
            break;
        }
        case "delete":
        {
            break;
        }
        case "denied":
        {
            break;
        }
    }
}

function ModuleAction_cpanel_users($params)
{
    $action=$params[0]??"list";
    switch($action)
    {
        
    }

}


function ModuleAction_cpanel_settings($params)
{
    
}

function ModuleAction_cpanel_menu($params)
{
    
    $action=$params[0]??"list";
    switch($action)
    {
        default:
        {
            if(!EngineCore::CheckPermission("menu.manager"))
            {
                EngineCore::WriteUserError("Not authorised to use this",1); // TODO error constants
                EngineCore::GTFO("/main/unauthorised");
                die;
            }
            $links = EngineCore::GetMenuLinks();
            $t=new TemplateProcessor("cpanel/menulinkeditor");
            $t->tokens['menu']=$links;
            EngineCore::SetPageContent($t->process(true));
            break;
        }
        case "update":
        {
            if(!EngineCore::CheckPermission("menu.manager"))
            {
                EngineCore::WriteUserError("Not authorised to use this",1); // TODO error constants
                HTTPHeaders::ContentType("text/json");
                echo '{"responseCode": "Denied"}';
                die;
            }
            $id=$params[1]??"";
            $menuitem=C::GetMenuLink($id);
            if(!$menuitem)
            {
                HTTPHeaders::ContentType("text/json");
                echo '{"responseCode": "NotFound"}';
                die;
            }
            $prop=EngineCore::POST("property","");
            $value=C::POST("value","");
            if($prop==="link")
            {
                C::SetMenuLinkHref($id, $value);
            }
            if($prop==="text")
            {
                C::SetMenuLinkText($id, $value);
            }
            HTTPHeaders::ContentType("text/json");
            $prop=addslashes($prop);
            echo '{"responseCode": "OK","responseValue": "'.$prop.'"}';
            die;
        }
        case "create":
        {
            $text=C::POST("text","");
            $link=C::POST("link","");
            if($text && $link)
            {
                C::AddMenuLink($link, $text);
                C::GTFO("/cpanel/menu/");
                die;
            }
        }
        case "delete":
        {
            $id=intval(C::POST("id",""));
            if($id>0)
            {
                C::DeleteMenuLink($id);
            }         
            C::GTFO("/cpanel/menu/");
            die;
        }
            
    }
}