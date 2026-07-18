<?php
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

    public const TABLE = 'calendar_events';
    public const SCHEMA = [
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
    public const FIELDS =["id",
        "title",
        "description",
        "category",
        "day",
        "month",
        "year",
        "hour",
        "minute",
        "duration",
        "user",
        "user_group",
        "active"
    ];
    public const TABLE_TYPES = "calendar_event_types";
    public const SCHEMA_TYPES = [
        "name"=>"varchar(255)",
        "marker_colour"=>"varchar(255)",
        "agenda_colour"=>"varchar(255)",
        "number_colour"=>"varchar(255)",
        "bg_colour"=>"varchar(255)",
        "priority"=>"int",
        "ghost"=>"int"
    ];
    public const FIELDS_TYPES = ["id",
        "name",
        "marker_colour",
        "number_colour",
        "agenda_colour",
        "bg_colour",
        "priority",
        "ghost"];
    function __construct($id,$title,$description,$category,$year,$month,$day,$hour,$minute,$duration,$active=true)
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
        $this->active = $active;
        if($duration==0)
        {
            $this->allDay=true;
        }
    }
    
    static function Load($id)
    {
        
        $q_event=DBHelper::Select(self::TABLE,self::FIELDS,['id'=>$id]);
        $row = DBHelper::RunRow($q_event, [$id]);
        if(!$row)
        {
            return null;
        }
        return new CalendarEvent($id,$row['title'],$row['description'],$row['category'],$row['year'],$row['month'],$row['day'],$row['hour'],$row['minute'],$row['duration'],$row['active']==1);
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
        DBHelper::Update(CalendarEvent::TABLE, $update, ['id'=>$this->id]);
    }
    
    public function Deactivate()
    {
        DBHelper::Update(CalendarEvent::TABLE,['active'=>0],['id'=>$this->id]);
    }
    
    public function ProcessForDisplay()
    {
        return self::PrepareForDisplay((array)$this,$this->year,$this->month,$this->day);
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
        DBHelper::Insert(CalendarEvent::TABLE,$row);
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
        $q_mapping = DBHelper::Select(self::TABLE_TYPES,self::FIELDS_TYPES,[]);
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
