<?php
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
        EngineCore::AppendTemplate("ticket/submitter");
    }
    else
    {
        $title=EngineCore::POST("title");
        $description=EngineCore::POST("description");
        $tid=Ticket::Create($title,$description,0,0);
        EngineCore::GTFO("/ticket/view/".$tid);
    }
    
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
    EngineCore::AddPageContent($tpl->process(true));
    
    
}

function ModuleFunction_ticket_GetNotClosed()
{
    $filters=["completedtime"=>0];
    $q=DBHelper::Select("tickets", ["id","type","subject","EvaID","title","submitter","time"], $filters,["time"=>"DESC"]);
    return DBHelper::RunTable($q,array_values($filters));
}
function ModuleFunction_ticket_GetWithStatus()
{
    $q="SELECT id,type,subject,EvaID,title,submitter,time,"
            . "(SELECT s.newstate "
            . "FROM ticket_state_changes s "
            . "WHERE t.id= s.ticketid "
            . "ORDER BY time DESC "
            . "LIMIT 1 "
            . ") as status "
            . "FROM tickets t "
            . "WHERE completedtime = 0 "
            . "ORDER BY status";
    return DBHelper::RunTable($q,[]);
}


function ModuleAction_ticket_list($params)
{
    $tickets= ModuleFunction_ticket_GetWithStatus();
    for($i=0;$i<count($tickets);$i++)
    {
        $tickets[$i]['status']=Ticket::ReadableStatusName($tickets[$i]['status']);
    }
    array_walk($tickets,function(&$v,$k){
        $v['ticketNumber']=Ticket::MakeTicketNumber($v['type'],$v['id']);
    });
    $tpl=new TemplateProcessor("ticket/ticketslist");
    $tpl->tokens['tickets']=$tickets;
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
        
        $obj=EVA::CreateObject("ticket.update",EVA::OWNER_NOBODY,["description"=>$text,"user_id"=>$user,"ticket.update.type"=>$type,"parent_object"=>$ticket->EvaId,"timestamp"=>time()]);
        if(isset($_FILES['update_attachment']))
        {    
            for($i=0;$i<count($_FILES['update_attachment']['name']);$i++)
            {
                $file=File::Upload($_FILES['update_attachment'],$i);
                if($file)
                {
                    $obj->AddAttribute("attachment",$file->blobid);
                }
                else
                {
                    EngineCore::WriteUserError("Uploading \"".$_FILES['update_attachment']['name'][$i]."\" failed.",0);
                    Logger::Log("Was unable to upload \"".$_FILES['update_attachment']['name'][$i]."\".",0,"upload error");
                }
            }
        }
        $obj->Save();
        EngineCore::GTFO("/ticket/view/".$tid);
        die();
    }
    
}