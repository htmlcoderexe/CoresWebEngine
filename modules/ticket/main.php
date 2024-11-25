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
    EngineCore::AddPageContent($tpl->process(true));
    
    
}

function ModuleAction_ticket_list($params)
{
    $filters=["completedtime"=>0];
    $q=DBHelper::Select("tickets", ["id","type","subject","EvaID","title","submitter","time"], $filters,["time"=>"DESC"]);
    $tickets=DBHelper::RunTable($q,array_values($filters));
    
    array_walk($tickets,function(&$v,$k){
        $v['ticketNumber']=Ticket::MakeTicketNumber($v['type'],$v['id']);
    });
    $tpl=new TemplateProcessor("ticket/ticketslist");
    $tpl->tokens['tickets']=$tickets;
    EngineCore::AddPageContent($tpl->process(true));
}