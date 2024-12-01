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
    
    /**
     * Creates and posts a ticket update.
     * @param int $parent Ticket's root object ID
     * @param string $text Update's text
     * @param int $user UID associated with the update
     * @param string $type Update type
     * @param array $files a slice of PHP's $_FILES array
     * @return TicketUpdate the resulting ticket update
     */
    public static function Create($parent,$text,$user,$type="info",$files=[])
    {
        $e= EVA::CreateObject("ticket.update",EVA::OWNER_NOBODY,["description"=>$text,"user_id"=>$user,"ticket.update.type"=>$type,"parent_object"=>$parent,"timestamp"=>time()]);
        // check if the array is actually usable
        if(isset($files['name']))
        {
            for($i = 0; $i < count($files['name']); $i++)
            {
                $file = File::Upload($files, $i);
                if($file)
                {
                    $e->AddAttribute("attachment", $file->blobid);
                }
                else
                {
                    EngineCore::WriteUserError("Uploading \"" . $files['name'][$i] . "\" failed.", 0);
                    Logger::Log("Was unable to upload \"" . $files['name'][$i] . "\".", 0, "upload error");
                }
            }
        }
        $e->Save();
        return new TicketUpdate($e->id);
    }
}
