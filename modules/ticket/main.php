<?php
require_once CLASS_DIR."TicketUpdate.php";
require_once CLASS_DIR."TicketGroup.php";
require_once CLASS_DIR."Ticket.php";
/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

function ModuleAction_ticket_default($params)
{
    ModuleAction_ticket_list($params);
}

function ModuleAction_ticket_submit($params)
{
    if(!EngineCore::POST('submit'))
    {
        $tpl = new TemplateProcessor("ticket/submitter");
        $gid = -1;
        if(count($params)>0)
        {
            $gid = intval(array_shift($params));
        }
        $groups = EVA::GetKVA("name","ticket_group");
        $tpl->tokens['group_id'] = $gid;
        $tpl->tokens['groups'] = $groups;
        
        EngineCore::SetPageContent($tpl->process(true));
    }
    else
    {
        $title=EngineCore::POST("title");
        $description=EngineCore::POST("description");
        $tid=Ticket::Create($title,$description,0,0);
        EngineCore::GTFO("/ticket/view/".$tid);
    }
    
}

function ModuleFunction_ticket_goback($error)
{
    EngineCore::WriteUserError($error,"error");
    EngineCore::GTFO("/ticket/groups/create");
    die();
}

function ModuleAction_ticket_view($params)
{
    $ticketNumber=$params[0];
    $ticket = new Ticket($ticketNumber);
    
    $tpl=new TemplateProcessor("ticket/ticketviewer");
    $tpl->tokens['number']=$ticket->GetNumber();
    $tpl->tokens['title']=$ticket->Title;
    $tpl->tokens['description']=$ticket->Description;
    $tpl->tokens['submitter']=$ticket->Submitter;
    $tpl->tokens['status']=Ticket::ReadableStatusName($ticket->CurrentState);
    $tpl->tokens['statuscode']=$ticket->CurrentState;
    $tpl->tokens['ticket_group_id'] = $ticket->Category;
    $groups = EVA::GetKVA("name","ticket_group");
    EngineCore::Lap2Debug("before the forloop");
    $assgroup = "!!NOWHERE!!";
    for($i=0;$i<count($groups);$i++)
    {
        if($groups[$i]['object_id'] == $ticket->Category)
        {
            $assgroup = $groups[$i]['value'];
        }
    }
    $tpl->tokens['ticket_group_name'] = $assgroup;
    $tpl->tokens['groups'] = $groups;
    
            EngineCore::Lap2Debug("before getting updates");
    if($ticket->GetUpdates()>0)
    {
        $updates=array_reverse($ticket->Updates);
        $updates_flat=[];
        foreach($updates as $update)
        {
            $flat_update=(array)$update;
            $flat_update['filedata']=[];
            if(!is_array($flat_update['files']))
            {
                $flat_update['files']=[$flat_update['files']];
            }
            foreach($flat_update['files'] as $file)
            {
                $fileobj=File::Load($file);
                if($fileobj)
                {
                    $flat_update['filedata'][]=$fileobj;
                }
            }
            $updates_flat[]=$flat_update;
        }
        $tpl->tokens['updates']=$updates_flat;
    }
    EngineCore::AddPageContent($tpl->process(true));
    
    
}

function ModuleFunction_ticket_GetNotClosed()
{
    $filters=["completedtime"=>0];
    $q=DBHelper::Select("tickets", ["id","type","subject","EvaID","title","submitter","time"], $filters,["time"=>"DESC"]);
    return DBHelper::RunTable($q,array_values($filters));
}
function ModuleFunction_ticket_GetWithStatus($group = -1)
{
    $cat ="";
    $params =[];
    if($group > -1)
    {
        $cat = "AND category = ?";
        $params[]=$group;
    }
    $q="SELECT id,type,subject,EvaID,title,submitter,time,"
            . "(SELECT s.newstate "
            . "FROM ticket_state_changes s "
            . "WHERE t.id= s.ticketid "
            . "ORDER BY time DESC "
            . "LIMIT 1 "
            . ") as status "
            . "FROM tickets t "
            . "WHERE completedtime = 0 "
            . $cat
            . "ORDER BY status";
    return DBHelper::RunTable($q,$params);
}


function ModuleAction_ticket_list($params)
{
    $gid = 0;
    $tpl=new TemplateProcessor("ticket/ticketslist");
    if(count($params)>0)
    {
        EngineCore::Dump2Debug($params);
        $gid = intval(array_shift($params));
        $tpl->tokens['gid'] = $gid;
        EngineCore::Dump2Debug($params);
    }
    $tickets= ModuleFunction_ticket_GetWithStatus($gid);
    for($i=0;$i<count($tickets);$i++)
    {
        $tickets[$i]['status']=Ticket::ReadableStatusName($tickets[$i]['status']);
    }
    array_walk($tickets,function(&$v,$k){
        $v['ticketNumber']=Ticket::MakeTicketNumber($v['type'],$v['id']);
    });
    $tpl->tokens['tickets']=$tickets;
    $tpl->tokens['ticketcount'] = count($tickets);
    if($gid != 0)
    {
        $group = new TicketGroup($gid);
        $tpl->tokens['groupname'] = $group->name;    
    }
    EngineCore::AddPageContent($tpl->process(true));
}

function ModuleAction_ticket_modify($params)
{
    $tid=$params[0]??"XXX000000";
    $ticket=new Ticket($tid);
    
    if(!$ticket)
    {
        EngineCore::GTFO("/ticket/");
        die();
    }
    
    $stateupdate=EngineCore::POST("newstate","");
    
    if($stateupdate)
    {
        $ticket->ChangeState($stateupdate);
        EngineCore::GTFO("/ticket/view/".$tid);
        die();
    }
    
    $update=EngineCore::POST("newupdate","");
    if($update)
    {
        $text=EngineCore::POST("update_text","");
        $user=User::GetCurrentUser()->userid;
        $type=EngineCore::POST("update_type","info");
        
        $ticket->AppendUpdate($text,$user,$type,$_FILES['update_attachment']??[]);
        EngineCore::GTFO("/ticket/view/".$tid);
        die();
    }
    $group = EngineCore::POST("ticket_group","");
    if($group)
    {
        $ticket->Assign($group);
        EngineCore::GTFO("/ticket/view/".$tid);
        die();
    }
    
}

function ModuleFunction_ticket_GetFuncGroups()
{
    $groups=DBHelper::RunTable(DBHelper::Select("user_groups", ["id","type","name","description","owner"], ["type"=> UserGroup::TYPE_FUNC]),[UserGroup::TYPE_FUNC]);
    return $groups;
}

function ModuleAction_ticket_groups($params)
{
    if(count($params) > 0)
    {
        $action = array_shift($params);
    }
    else
    {
        $action = "all";
    }
    switch($action)
    {
        case "create":
        {
            $tpl = new TemplateProcessor("ticket/group_edit");
            $groups = ModuleFunction_ticket_GetFuncGroups();
            $tpl->tokens["groups"] = $groups;
            $tpl->tokens["func_group"] = -1;
            EngineCore::SetPageContent($tpl->process(true));
            break;
        }
        case "edit":
        {
            $gid = (int) array_shift($params);
            $group = new TicketGroup($gid);
            if(!isset($group->name))
            {
                EngineCore::GTFO("/ticket/groups/all");
                return;
            }
            $tpl = new TemplateProcessor("ticket/group_edit");
            $groups = ModuleFunction_ticket_GetFuncGroups();
            $tpl->tokens["groups"] = $groups;
            $tpl->tokens["gname"] = $group->name;
            $tpl->tokens["description"] = $group->description;
            $tpl->tokens["gid"] = $group->id;
            $tpl->tokens["func_group"] = $group->func_group;
            EngineCore::SetPageContent($tpl->process(true));
        
            break;
        }
        case "submit":
        {
            $name = EngineCore::POST("gname","");
            $desc = EngineCore::POST("description","");
            $gid = EngineCore::POST("func_group","");
            $id = EngineCore::POST("gid","");
            
            if($id == -1)
            {
                if($name=="")
                {
                    ModuleFunction_ticket_goback("Please enter a name.");
                }
                if($desc =="")
                {
                    ModuleFunction_ticket_goback("Description shouldn't be empty.");
                }
                $exists = EVA::GetByProperty("name", $name, "ticket_group");
                if(count($exists) > 0)
                {
                    ModuleFunction_ticket_goback("Group <strong>$name</strong> already exists.");
                }
                if($gid == -1)
                {
                    $new_user_group = UserGroup::Create("ticket_" . strtolower($name), "fg for " . $name, UserGroup::TYPE_FUNC);
                    $gid = $new_user_group->id;
                }
                $new_group = TicketGroup::Create($name,$desc,$gid);
                EngineCore::GTFO("/ticket/groups/edit/".$new_group->id);
                return;
            }
            $group = new TicketGroup($id);
            $group->name = $name;
            $group->description = $desc;
            $group->func_group = $gid;
            $group->Update();
            EngineCore::GTFO("/ticket/groups/edit/".$group->id);
            return;
            
        }
        case "all":
        default:
        {
            $tpl = new TemplateProcessor("ticket/groups_list");
            $groups = EVA::GetKVA("name","ticket_group");
            for($i=0;$i<count($groups);$i++)
            {
                $gid =$groups[$i]['object_id'];
                $q = DBHelper::Count("tickets", "category", ["category"=>$gid]);
                $groups[$i]['count'] = $q;
            }
            $tpl->tokens["groups"] = $groups;
            EngineCore::SetPageContent($tpl->process(true));
            
            return;
        }
    }
}

