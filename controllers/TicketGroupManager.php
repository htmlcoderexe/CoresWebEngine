<?php


namespace Controllers;

use Common\DBHelper;
use Cores\EngineCore;
use Models\Tickets\TicketGroup;
use Models\User\UserGroup;
use PostRoute;
use Route;

/**
 * Description of TicketGroupManager
 *
 */
class TicketGroupManager
{
    public static function GetFunctionalGroups()
    {
        return DBHelper::RunTable(DBHelper::Select("user_groups", ["id","type","name","description","owner"], ["type"=> UserGroup::TYPE_FUNC]),[UserGroup::TYPE_FUNC]);
    }
    
    #[Route('tickets/groups/index','ticket.group')]
    public static function GroupIndex()
    {
        return ['entity_type'=>'ticket/groups',
            'groups'=>TicketGroup::GetAllGroups()
        ];
    }
    
    #[Route('tickets/groups/create','ticket.group')]
    public static function ShowCreateGroupForm()
    {
        
        return ['entity_type'=>'ticket/editgroup',
            'groups'=>self::GetFunctionallGroups(),
            'func_group'=>-1
        ];
    }
    
    #[Route('tickets/groups/edit','ticket.group')]
    public static function ShowEditGroupForm($id = 0)
    {
        $id = intval($id);
        $group = TicketGroup::Load($id);
        if(!$group)
        {
            return EngineCore::Error(404, 'Group does not exist');
        }
        $e = (array)$group;
        $e['entity_type']='ticket/editgroup';
        $e['groups']=self::GetFunctionalGroups();
        $e['header'] = "Editing $group->name";
        return $e;
    }
    
    #[PostRoute('tickets/groups/submit','ticket.group')]
    public static function UpdateGroup()
    {
        $e = ['entity_type'=>'ticket/editgroup'];
        $e['name'] = EngineCore::POST("name","");
        $e['description'] = EngineCore::POST("description","");
        $e['func_group'] = EngineCore::POST("func_group","");
        $e['id'] = EngineCore::POST("id","");
        $e['groups']=self::GetFunctionalGroups();
        
        if($e['name']=="")
        {
            $e['error'] = "Please enter a name.";
            return $e;
        }
        if($e['description'] =="")
        {
            $e['error'] = "Description shouldn't be empty.";
            return $e;
        }
        
        if($e['id'] == -1)
        {
            
            $exists = TicketGroup::FindByName($e['name']);
            if($exists)
            {
                $e['error'] = "Group <strong>{$e['name']}</strong> already exists.";
                return $e;
            }
            if($e['func_group'] == -1)
            {
                $new_user_group = UserGroup::Create("ticket_" . strtolower($e['name']), "fg for " . $e['name'], UserGroup::TYPE_FUNC);
                $e['func_group'] = $new_user_group->id;
            }
            $new_group = TicketGroup::Create($e['name'],$e['description'],$e['func_group']);
            EngineCore::GTFO("/tickets/groups/edit/".$new_group->id);
        }
        $group = TicketGroup::Load($e['id']);
        $group->name = $e['name'];
        $group->description = $e['description'];
        $group->func_group = $e['func_group'];
        $group->Update();
        EngineCore::GTFO("/tickets/groups/edit/".$group->id);
    }
}
