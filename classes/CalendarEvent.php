<?php
Module::DemandProperty("calendar.duration", "Duration", "The duration of an event.");
class CalendarEvent
{
    public $id;
    public $timestamp;
    
    public $date;
    public $startTime;
    public $duration;
    public $allDay;
    
    
    public $title;
    public $description;
    
    public $isValid=true;
    public EVA $EvaInstance;

    function __construct($id)
    {
        $e = new EVA($id);
        if($e)
        {
            $this->title=$e->attributes['title']??$this->Invalidate();
            $this->date=$e->attributes['calendar.date']??$this->Invalidate();
            $this->startTime=$e->attributes['calendar.time']??$this->Invalidate();
            $this->duration=$e->attributes['calendar.duration']??"01:00";
            $this->description=$e->attributes['description']??$this->Invalidate();
            $this->EvaInstance=$e;
        }
        else
        {
            return;
        }
        
    }
    
    function Invalidate()
    {
        $this->isValid=false;
        return "";
    }

    function Create()
    {

    }
    
    public function Commit()
    {
        
    }
} 
