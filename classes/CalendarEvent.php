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
    
    public $year;
    public $month;
    public $day;
    
    public $hour;
    public $minute;
    
    public $duration;
    
    
    public $title;
    public $description;
    
    public $type;
    
    public $isValid=true;
    public $allDay;

    function __construct($id,$title,$description,$category,$year,$month,$day,$hour,$minute,$duration)
    {
        $this->id=$id;
        $this->title=$title;
        $this->description = $description;
        $this->type=$category;
        $this->year=$year;
        $this->month = $month;
        $this->day=$day;
        $this->hour = $hour;
        $this->minute = $minute;
        $this->duration = $duration;
        if($duration==60*24)
        {
            $this->allDay=true;
        }
    }
    
    static function Load($id)
    {
        $fields=[
            "id",
            "title", "description","category",
            "day","month","year",
            "hour","minute", "duration"
            ];
        $q_event=DBHelper::Select("calendar_events",$fields,['id'=>$id]);
        $row = DBHelper::RunRow($q_event, [$id]);
        if(!$row)
        {
            return null;
        }
        return new CalendarEvent($id,$row['title'],$row['description'],$row['category'],$row['year'],$row['month'],$row['day'],$row['hour'],$row['minute'],$row['duration']);
    }
    
    public function Save()
    {
        $update = [
        "title"=>$this->title,
        "description"=>$this->description,
        "category"=>$this->type,
        "day"=>$this->day,
        "month"=>$this->month,
        "year"=>$this->year,
        "hour"=>$this->hour,
        "minute"=>$this->minute,
        "duration"=>$this->duration,
        "active"=>$this->active
        ];
        DBHelper::Update("calendar_events", $update, ['id'=>$this->id]);
    }

    static function Create($title, $description, $category,$year,$month,$day, $hour,$minute, $duration)
    {
        $uid=EngineCore::$CurrentUser->userid;
        
        $row = [
            null,
            $title,$description,$category,
            $day,$month,$year,$hour,$minute,$duration,
            $uid,0,1
        ];
        DBHelper::Insert("calendar_events",$row);
        $id=DBHelper::GetLastId();
        return new CalendarEvent($id,$title,$description,$category,$year,$month,$day,$hour,$minute,$duration);
    }
    
    static function SplitDate($date)
    {
        return explode("-",$date);
    }
    static function JoinDate($y,$m,$d)
    {
        return $y."-".str_pad($m,2,"0", STR_PAD_LEFT)."-".str_pad($d,2,"0", STR_PAD_LEFT);
    }
    static function SplitHHMM($time)
    {
        return explode(":",$time);
    }
    static function JoinHHMM($hh,$mm)
    {
        return str_pad($hh,2,"0", STR_PAD_LEFT).":".str_pad($mm,2,"0", STR_PAD_LEFT);
    }
    static function MinutesToArray($minutes)
    {
        return [
            str_pad(
                    floor($minutes / 60),2,"0", STR_PAD_LEFT),
            str_pad(
                    $minutes % 60,2,"0", STR_PAD_LEFT)
            ];
    }
    static function HHMMFromMinutes($minutes)
    {
        list($hh,$mm)=self::MinutesToArray($minutes);
        return $hh.":".$mm;
    }
    static function MinutesFromHHMM($time)
    {
        list($hh,$mm)=self::SplitHHMM($time);
        return $hh*60+$mm;
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
    
    public static function GetEventTypes($flat=false)
    {
        $q_mapping = DBHelper::Select("calendar_event_types",["id","number_colour","marker_colour","agenda_colour","bg_colour","priority","ghost"],[]);
        $mapping_result = DBHelper::RunTable($q_mapping,[]);
        $mapping=[];
        if($flat)
        {
            return $mapping_result;
        }
        foreach($mapping_result as $result)
        {
            $mapping[$result['id']]=$result;
        }
        return $mapping;
    }
    
    
} 
