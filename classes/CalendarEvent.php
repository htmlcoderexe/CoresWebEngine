<?php
class CalendarEvent
{
    public $id;
    public $timestamp;
    
    public $date;
    public $startTime;
    public $endTime;
    public $allDay;
    
    
    public $title;
    public $description;
    
    
    private $EvaInstance;

    function __construct($id)
    {
        $e = new EVA($id);
        if($e)
        {
            $this->title=$e->attributes['title'];
            $this->date=$e->attributes['calendar.date'];
            $this->startTime=$e->attributes['calendar.time'];
            $this->description=$e->attributes['description'];
        }
        else
        {
            return;
        }
        
    }

    function Create()
    {

    }
    
    public function Commit()
    {
        
    }
} 
