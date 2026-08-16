<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Controllers;

use Cores\EngineCore;
use Models\File;
use Models\Tickets\Ticket;
use Models\Tickets\TicketGroup;
use Models\Tickets\TicketInfo;
use Models\User\User;
use PostRoute;
use Route;

/**
 * Description of TicketController
 *
 * @author admin
 */
class TicketController
{
    //put your code here
    
    #[Route('tickets/view','tickets.view')]
    public static function ShowTicket($ticketNumber = '')
    {
        $ticket = Ticket::Load($ticketNumber);
        if(!$ticket)
        {
            return EngineCore::Error(404, "Ticket not found");
        }
        $e = (array)$ticket->info;
        $e['entity_type']='ticket/view';
        $e['number']=$ticket->GetNumber();
        $e['status']=Ticket::ReadableStatusName($ticket->info->last_status);
        $e['ticket_group_id'] = $ticket->info->group;
        $groups = TicketGroup::GetAllGroups();
        EngineCore::Lap2Debug("before the forloop");
        $assgroup = "!!NOWHERE!!";
        $groupmap = [];
        for($i=0;$i<count($groups);$i++)
        {
            $groupmap[$groups[$i]['gid']]=$groups[$i]['name'];
            if(intval($groups[$i]['gid']) == $ticket->info->group)
            {
                $assgroup = $groups[$i]['name'];
            }
        }
        $e['ticket_group_name'] = $assgroup;
        $e['groups'] = $groups;

        EngineCore::Lap2Debug("before getting updates");
        $formatted_updates = [];
        foreach($ticket->updates as $update)
        {
            $flat = (array) $update;
            $flat['groupname'] = $groupmap[$update->newgroup] ?? "UNKNOWN";
            $flat['statusname'] = TicketInfo::TICKET_STATUSES[$update->newstate]?? "N/A";
            $formatted_updates[]=$flat;
        }
        $e['updates']=$formatted_updates;
        return $e;
    }
    
    #[Route('tickets/default')]
    public static function Index()
    {
        EngineCore::GTFO('/tickets/list');
    }
    
    #[Route('tickets/list','tickets.view')]
    public static function ListTickets($gid = -1)
    {
        $e = ['entity_type'=>"ticket/list"];
        
        if($gid!=-1)
        {
            $e['gid'] = $gid;
            
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
        $e['tickets']=$ticket_view;
        $e['ticketcount'] = count($tickets);
        if($gid != -1)
        {
            $group = TicketGroup::Load($gid);
            if($group)
            {
                $e['groupname'] = $group->name;  
            }
            else
            {
                $e['groupname'] = "INVALID_GROUP";    
            }

        }
        return $e;
    }
    
    #[Route('tickets/submit','tickets.submit')]
    public static function ShowSubmitForm($gid = -1)
    {
        return ['entity_type'=>'tickets/submit',
            'groups' => TicketGroup::GetAllGroups,
            'group_id' => intval($gid)
        ];
    }
    
    #[PostRoute('tickets/submit','tickets.submit')]
    public static function ReceiveTicket()
    {
        $cu = EngineCore::$CurrentUser->userid;
        $title=EngineCore::POST("title");
        $description=EngineCore::POST("description");
        $ticket = Ticket::Create(title: $title, description: $description, submitter: $cu,type: TicketInfo::TYPE_INC);
        $tid=$ticket->id;
        EngineCore::GTFO("/tickets/view/".$tid);
    }
    
    #[PostRoute('tickets/modify', 'tickets.submit')]
    public static function ReceiveTicketUpdate($tid = 'XXX000000')
    {
        $ticket=Ticket::Load($tid);

        if(!$ticket)
        {
            return EngineCore::Error(404);
        }

        $stateupdate=intval(EngineCore::POST("newstate","-1"));

        if($stateupdate!=-1)
        {
            $ticket->ChangeState($stateupdate);
            EngineCore::GTFO("/tickets/view/".$tid);
        }

        $update=EngineCore::POST("newupdate","");
        if($update)
        {
            $text=EngineCore::POST("update_text","");
            $user=User::GetCurrentUser()->userid;
            $type=EngineCore::POST("update_type","info");
            $files_in = $_FILES['update_attachment']??[];
            $files = [];
            if(isset($files_in['name']))
            {
                for($i=0;$i<count($files_in['name']);$i++)
                {
                    $file = File::Upload($files_in, $i);
                    if($file)
                    {
                        $files[]=$file->blobid;
                    }
                }
            }
            $ticket->AppendCommentUpdate(text:$text,user:$user,files:$files);
            EngineCore::GTFO("/tickets/view/".$tid);
        }
        $group = EngineCore::POST("ticket_group","");
        if($group)
        {
            $ticket->AssignGroup(gid: intval($group));
            EngineCore::GTFO("/tickets/view/".$tid);
        }
    }
    
    public static function GetOpenTickets()
    {
        $filters=["completedtime"=>0];
        $q=DBHelper::Select("tickets", ["id","type","subject","EvaID","title","submitter","time"], $filters,["time"=>"DESC"]);
        return DBHelper::RunTable($q,array_values($filters));
    }
    
    public static function GetWithStatus($group = -1)
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
}
