<?php


Module::DemandProperty("attachment","Attachment","BLOB id of attached file");
Module::DemandProperty("ticket.update.type","Ticket update type","Type of an update attached to a ticket");
/**
 * Description of TicketUpdate
 *
 * @author admin
 */
class TicketUpdate
{
    //put your code here
    
    public $ticket="";
    public $text;
    public $user;
    public $files;
    public $type;
    public $time;
    
    public function __construct($EvaID)
    {
        $e=new EVA($EvaID);
        if(!$e)
        {
            return;
        }
        $this->ticket=$e->attributes['parent_object'];
        $this->text=$e->attributes['description'];
        $this->user=$e->attributes['user_id'];
        $this->type=$e->attributes['ticket.update.type'];
        $this->files=$e->attributes['attachment']??[];
        $this->time=$e->attributes['timestamp'];
        
    }
    
    
    public static function Create($parent,$text,$user,$files=[])
    {
        EVA::CreateObject("ticket.update",EVA::OWNER_NOBODY,["description"=>$text,"user_id"=>$user,"ticket.update.type"=>$type,"parent_object"=>$parent,"timestamp"=>time()]);
    }
}
