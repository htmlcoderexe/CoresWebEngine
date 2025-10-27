<?php
Module::DemandProperty("calendar.duration", "Duration", "The duration of an event.");
Module::DemandProperty("calendar.event_type","Event type","Type of a calendar event.");
Module::DemandProperty("calendar.tagcolour","Calendar colour","How the event is marked in the month view.");
Module::DemandProperty("calendar.agendacolour","Schedule colour","How the event is marked in the week view.");
Module::DemandProperty("name", "Name", "Name of something.");
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
    
    public $type;
    
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
            $this->type=$e->attributes['calendar.event_type']??"";
            $this->EvaInstance=$e;
        }
        else
        {
            $this->Invalidate();
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
