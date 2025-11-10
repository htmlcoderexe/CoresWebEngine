<?php

$calendar_event_schema = [
    // body
    "title"=>"varchar(255)",
    "description"=>"varchar(2000)",
    "category"=>"int",
    // time
    "day"=>"int",
    "month"=>"int",
    "year"=>"int",
    "hour"=>"int",
    "minute"=>"int",
    "duration"=>"int", // in minutes
    // AAA stuff
    "user"=>"int",
    "user_group"=>"int",
    "active"=>"int"
    ];
$calendar_event_types = [
    "name"=>"varchar(255)",
    "marker_colour"=>"varchar(255)",
    "agenda_colour"=>"varchar(255)",
    "number_colour"=>"varchar(255)",
    "bg_colour"=>"varchar(255)",
    "priority"=>"int",
    "ghost"=>"int"
    ];
Module::DemandTable("calendar_event_types",$calendar_event_types);
Module::DemandTable("calendar_events", $calendar_event_schema);
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
    
    public static function PrepareForDisplay($value,$y,$m,$d)
    {
        $value['day']=str_pad($d,2,"0", STR_PAD_LEFT);
        $value['month']=str_pad($m,2,"0", STR_PAD_LEFT);
        $dayminute = $value['hour']*60+$value['minute'];
        $doneminute =$dayminute+$value['duration'];
        $value['dayminute']=$dayminute;
        $value['doneminute']=$doneminute;

        $value['hour']=str_pad($value['hour'],2,"0", STR_PAD_LEFT);
        $value['minute']=str_pad($value['minute'],2,"0", STR_PAD_LEFT);
        $value['year']=$y;
        $value['recurrer'] = $value['id'];
        $value['duration_minutes'] = str_pad($value['duration'] % 60,2,"0", STR_PAD_LEFT);
        $value['duration_hours'] = str_pad(floor($value['duration'] / 60),2,"0", STR_PAD_LEFT);
        $value['done_minutes'] = str_pad($value['doneminute'] % 60,2,"0", STR_PAD_LEFT);
        $value['done_hours'] = str_pad(floor($value['doneminute'] / 60),2,"0", STR_PAD_LEFT);
        return $value;
    }
    
    
} 
