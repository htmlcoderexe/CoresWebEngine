<?php
class CalendarEvent
{
    public $id;
    public $timestamp;
    
    public $targetDate;
    public $startTime;
    public $endTime;
    public $allDay;
    
    
    public $title;
    public $description;
    
    
    private $EvaInstance;

    function __construct($id)
    {
        $EvaInstance = new EVA($id);
        if($EvaInstance)
        {
            
        }
        else
        {
            
        }
        
    }

    function Create()
    {

    }
    
    public function Commit()
    {
        
    }
} 
