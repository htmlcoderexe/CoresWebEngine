<?php


/**
 * Description of KBPageInfo
 *
 */
class KBPageInfo
{
    public $id;
    public $title;
    public $latest;

    
    public $text;
    public $html;
    public $ejsdoc;
    
    public $project_id;
    
    public $created;
    public $modified;
    public $creator;
    
    
    public function __construct($id,$title,$ejsdoc,$text,$html,$created,$latest,$project_id = 0)
    {
        $this->id=$id;
        $this->title=$title;
        $this->latest=$latest;
        
        $this->text=$text;
        $this->ejsdoc=$ejsdoc;
        $this->html=$html;
        
        $this->project_id=$project_id;
        $this->created=$created;
    }
}
