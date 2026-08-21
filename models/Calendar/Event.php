<?php
namespace Models\Calendar;

use Common\DBHelper;
use Cores\EngineCore;

class Event
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
    
    public $active;

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
        return new Event($id,$row['title'],$row['description'],$row['category'],$row['year'],$row['month'],$row['day'],$row['hour'],$row['minute'],$row['duration'],$row['active']==1);
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
        DBHelper::Update(Event::TABLE, $update, ['id'=>$this->id]);
    }
    
    public function Deactivate()
    {
        DBHelper::Update(Event::TABLE,['active'=>0],['id'=>$this->id]);
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
        DBHelper::Insert(Event::TABLE,$row);
        $id=DBHelper::GetLastId();
        return new Event($id,$title,$description,$category,$year,$month,$day,$hour,$minute,$duration);
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
