<?php
require_once CLASS_DIR."TicketUpdate.php";
require_once CLASS_DIR."TicketUpdateAttachment.php";
require_once CLASS_DIR."TicketGroup.php";
require_once CLASS_DIR."Ticket.php";
require_once CLASS_DIR."TicketInfo.php";
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
        $groups = TicketGroup::GetAllGroups();
        $tpl->tokens['group_id'] = $gid;
        $tpl->tokens['groups'] = $groups;
        
        EngineCore::SetPageContent($tpl->process(true));
    }
    else
    {
        $cu = EngineCore::$CurrentUser->userid;
        $title=EngineCore::POST("title");
        $description=EngineCore::POST("description");
        $ticket = Ticket::Create(title: $title, description: $description, submitter: $cu,type: TicketInfo::TYPE_INC);
        $tid=$ticket->id;
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
    $ticket = Ticket::Load($ticketNumber);
    
    $tpl=new TemplateProcessor("ticket/ticketviewer");
    $tpl->tokens['number']=$ticket->GetNumber();
    $tpl->tokens['title']=$ticket->info->title;
    $tpl->tokens['description']=$ticket->info->description;
    $tpl->tokens['submitter']=$ticket->info->submitter;
    $tpl->tokens['status']=Ticket::ReadableStatusName($ticket->info->last_status);
    $tpl->tokens['statuscode']=$ticket->info->last_status;
    $tpl->tokens['ticket_group_id'] = $ticket->info->group;
    $groups = TicketGroup::GetAllGroups();
    EngineCore::Lap2Debug("before the forloop");
    $assgroup = "!!NOWHERE!!";
    $groupmap = [];
    for($i=0;$i<count($groups);$i++)
    {
        $groupmap[$groups[$i]['id']]=$groups[$i]['name'];
        if(intval($groups[$i]['id']) == $ticket->info->group)
        {
            $assgroup = $groups[$i]['name'];
        }
    }
    $tpl->tokens['ticket_group_name'] = $assgroup;
    $tpl->tokens['groups'] = $groups;
    
            EngineCore::Lap2Debug("before getting updates");
    $formatted_updates = [];
    foreach($ticket->updates as $update)
    {
        $flat = (array) $update;
        $flat['groupname'] = $groupmap[$update->newgroup] ?? "UNKNOWN";
        $flat['statusname'] = TicketInfo::TICKET_STATUSES[$update->newstate]?? "N/A";
        $formatted_updates[]=$flat;
    }
    $tpl->tokens['updates']=$formatted_updates;
    /*  
    if(false && $ticket->updates)
    {
        $updates=array_reverse($ticket->updates);
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
    //*/
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
    $gid = -1;
    $tpl=new TemplateProcessor("ticket/ticketslist");
    if(count($params)>0)
    {
        EngineCore::Dump2Debug($params);
        $gid = intval(array_shift($params));
        $tpl->tokens['gid'] = $gid;
        EngineCore::Dump2Debug($params);
    }
    $tickets= TicketInfo::GetTickets($gid);
    $ticket_view = [];
    foreach($tickets as $ticket)
    {
        $flat = (array)$ticket;
        $flat['status'] = Ticket::ReadableStatusName($ticket->last_status);
        $flat['ticketNumber'] = Ticket::MakeTicketNumber($ticket->type, $ticket->id);
        $ticket_view[]=$flat;
    }
    $tpl->tokens['tickets']=$ticket_view;
    $tpl->tokens['ticketcount'] = count($tickets);
    if($gid != -1)
    {
        $group = TicketGroup::Load($gid);
        if($group)
        {
            $tpl->tokens['groupname'] = $group->name;  
        }
        else
        {
            $tpl->tokens['groupname'] = "INVALID_GROUP";    
        }
       
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
            // $groups = EVA::GetKVA("name","ticket_group");
            $groups = TicketGroup::GetAllGroups();
            $tpl->tokens["groups"] = $groups;
            EngineCore::SetPageContent($tpl->process(true));
            
            return;
        }
    }
}


function ModuleAction_ticket_migrate($params)
{
    $user=User::GetCurrentUser();
    if(!$user->HasPermission("super"))
    {
        EngineCore::FromWhenceYouCame();
        die;
    }
    
    // get groups
    $group_fields = ['name','description','user_group'];
    $old_groups = EVA::GetAsTable($group_fields, 'ticket_group');
    var_dump($old_groups);
    //die;
    $group_mapping = [];
    DBHelper::MakeTable(name: TicketGroup::TABLE, fields: TicketGroup::SCHEMA, useID: true);
    // migrate groups and create a reference from old ID to new group
    foreach($old_groups as $old_group_id=>$old_group_data)
    {
        $group = TicketGroup::Create(name: $old_group_data['name'], description: $old_group_data['description'], func_group: $old_group_data['user_group']);
        $group_mapping[$old_group_id]=$group;
    }
    var_dump($group_mapping);
    // get the basic tickets from table
    $old_fields = ['id','type',	'submitter', 'subject',	'EvaID', 'title', 'description', 'time', 'category', 'completedtime', 'owner'];
    $tickets = DBHelper::GetRowsByField(table: "tickets", field:"1", value:"1", fields:$old_fields);
    var_dump($tickets);
    $eva_ticket_fields = [];
    
    // get ticket state changes from table
    $old_fields2 = ['id','ticketid','newstate','time'];
    $statechanges = DBHelper::GetRowsByField(table: "ticket_state_changes", field:"1", value:"1", fields:$old_fields2);
    
    $statechanges_by_ticket = [];
    foreach($statechanges as $statechange)
    {
        if(!isset($statechanges_by_ticket[$statechange['ticketid']]))
        {
            $statechanges_by_ticket[$statechange['ticketid']]=[];
        }
        $statechanges_by_ticket[$statechange['ticketid']][]=$statechange;
    }
    var_dump($statechanges_by_ticket);
    // get ticket updates from EVA
    
    $old_update_fields = ["description","user_id","ticket.update.type","parent_object","timestamp","attachment"];
    $old_updates = EVA::GetAsTable($old_update_fields,'ticket.update');
    var_dump($old_updates);
    // slot updates into corresponding ticket EVA IDs
    $updates_by_evaid = [];
    foreach($old_updates as $update_id=>$update)
    {
        $eid = $update['parent_object'];
        if(!isset($updates_by_evaid[$eid]))
        {
            $updates_by_evaid[$eid]=[];
        }
        if(isset($update['attachment']))
        {
            if(!is_array($update['attachment']))
            {
                $update['attachment'] = [$update['attachment']];
            }
        }
        else
        {
            $update['attachment'] = [];
        }
        $updates_by_evaid[$eid][]=$update;
    }
    var_dump($updates_by_evaid);
    $timestampsort = function($a,$b)
    {
        return intval($a['timestamp']) <=> intval($b['timestamp']); 
    };
    $timesort = function($a,$b)
    {
        return intval($a['time']) <=> intval($b['time']); 
    };
    echo "so far so good";
    //die;
    
    DBHelper::MoveTable("tickets", "tickets_old");
    DBHelper::MakeTable(name: TicketInfo::TABLE, fields: TicketInfo::SCHEMA, useID: true);
    DBHelper::MakeTable(name: TicketUpdate::TABLE, fields: TicketUpdate::SCHEMA, useID: true);
    DBHelper::MakeTable(name: TicketUpdateAttachment::TABLE, fields: TicketUpdateAttachment::SCHEMA, useID: true);
    
    foreach($tickets as $old_ticket)
    {
        $lastupdate = 0;
        $ticket = Ticket::Create(
                title: $old_ticket['title'],
                description: $old_ticket['description'],
                type: intval($old_ticket['type']),
                user: intval($old_ticket['owner']),
                submitter: intval($old_ticket['submitter']),
                subject: intval($old_ticket['subject']),
                attachments: [],
                time: $old_ticket['time']
                );
        $ticket_updates = $updates_by_evaid[$old_ticket['EvaID']] ?? [];
        usort($ticket_updates, $timestampsort);
        // all the updates stored in EVA are of the comment/attachment variety
        foreach($ticket_updates as $ticket_update)
        {
            $time = intval($ticket_update['timestamp']);
            $lastupdate = max($lastupdate, $time);
            $update = TicketUpdate::Create(
                    ticket_id: $ticket->info->id, 
                    user: intval($ticket_update['user_id']),
                    type: TicketUpdate::TYPE_COMMENT, 
                    newtext: $ticket_update['description'],
                    files: $ticket_update['attachment'],
                    time: $time);
            var_dump($ticket_update['attachment']);
        }
        $changes = $statechanges_by_ticket[$old_ticket['id']] ?? [];
        usort($changes, $timesort);
        foreach($changes as $change)
        {
            $time = intval($change['time']);
            $lastupdate = max($lastupdate, $time);
            $status = $change['newstate'];
            $update = TicketUpdate::Create(
                    ticket_id: $ticket->info->id,
                    user: $ticket->info->owner,
                    type: TicketUpdate::TYPE_STATUSCHANGE,
                    newstate: $status,
                    time: $time
            );
            $ticket->info->last_status = $status;
            if($status == TicketInfo::STATUS_CLOSED)
            {
                $ticket->info->completed_time = $time;
            }
        }
        $update = TicketUpdate::Create(
                    ticket_id: $ticket->info->id,
                    user: $ticket->info->owner,
                    type: TicketUpdate::TYPE_GROUPCHANGE,
                    newgroup: intval($group_mapping[$old_ticket['category']]->id),
                    time: $ticket->info->time
            );
        $ticket->info->group =intval($group_mapping[$old_ticket['category']]->id);
        $ticket->info->Update(true);
        var_dump($ticket);
    }
    echo "<h2>Done!</h2><a href='migraterevert'>Revert</a>";
}
function ModuleAction_ticket_migraterevert($params)
{
    $user=User::GetCurrentUser();
    if(!$user->HasPermission("super"))
    {
        EngineCore::FromWhenceYouCame();
        die;
    }
        DBHelper::DeleteTable(table: TicketUpdate::TABLE);
        DBHelper::DeleteTable(table: TicketUpdateAttachment::TABLE);
        DBHelper::DeleteTable(table: TicketGroup::TABLE);
        DBHelper::DeleteTable(table: TicketInfo::TABLE);
        DBHelper::MoveTable("tickets_old", "tickets");
    echo "<h2>Done!</h2><a href='migrate'>Try again</a>";
        
}