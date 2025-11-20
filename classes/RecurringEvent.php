<?php


$recurrerr_event_schema = [
    // event data
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
    // recurrer specific data
    "end"=>"int", // Unix Timestamp
    "recur_type"=>"varchar(255)",
    "recur_data"=>"varchar(255)",
    // AAA data
    "user"=>"int",
    "user_group"=>"int",
    "active"=>"int"
    ];

$exception_schema = [
    "recurrer_id"=>"int",
    "day"=>"int",
    "month"=>"int",
    "year"=>"int"
];
Module::DemandTable("calendar_exceptions", $exception_schema);
Module::DemandTable("calendar_recurring_events", $recurrerr_event_schema);
Module::DemandProperty("calendar.recurring.type", "Duration", "The duration of an event.");
Module::DemandProperty("calendar.recurring.start_date", "Start date", "The starting date of a recurring event.");
Module::DemandProperty("calendar.recurring.end_date", "End date", "The ending date of a recurring event.");
Module::DemandProperty("calendar.recurring.data","Event type","Type of a calendar event.");
Module::DemandProperty("calendar.recurring.latest_id","Calendar colour","How the event is marked in the month view.");
Module::DemandProperty("calendar.recurring.latest_date","Schedule colour","How the event is marked in the week view.");
Module::DemandProperty("calendar.event.parent","Schedule colour","Event's parent recurrer.");
Module::DemandProperty("name", "Name", "Name of something.");
/**
 * Description of RecurringEvent
 *
 * @author 
 */
class RecurringEvent
{
    
    public $id;
    
    public $title;
    public $description;
    public $category;
    
    public $year;
    public $month;
    public $day;
    
    public $hour;
    public $minute;
    public $duration;
    
    public $end_date;
    public $recur_type;
    public $recur_data;
    
    public $allDay;
    
    public const RECUR_WEEKLY = "week";
    public const RECUR_DAYS = "day";
    public const RECUR_MONTH = "month";
    
    public function __construct($id,$title,$description,$category,$year,$month,$day,$hour,$minute,$duration,$recur_type,$recur_data,$enddate=0)
    {
        $this->id=$id;
        $this->title = $title;
        $this->description = $description;
        $this->category=$category;
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
        
        $this->end_date = $enddate; 
        $this->recur_type = $recur_type;
        $this->recur_data = $recur_data;
                
    }
    
    public static function Load($id)
    {
        $fields = [
            "id",
            "title", "description","category",
            "day","month","year",
            "hour","minute", "duration",
            "end","recur_type","recur_data"
            ];
        $q_rec = DBHelper::Select("calendar_recurring_events", $fields, ['id'=>$id]);
        $row = DBHelper::RunRow($q_rec,[$id]);
        if(!$row)
        {
            return null;
        }
        return new RecurringEvent($id, 
                $row['title'],
                $row['description'],
                $row['category'],
                $row["year"],$row["month"],$row["day"],
                $row['hour'],$row['minute'],
                $row['duration'],
                $row['recur_type'],
                $row['recur_data'],
                $row["end"]);
        
    }
    
    public function Save()
    {
        $update = [
            "title"=>$this->title,
            "description"=>$this->description,
            "catergory"=>$this->event_type,
            "year"=>$this->year,"month"=>$this->month,"day"=>$this->day,
            "hour"=>$this->hour,"minute"=>$this->minute,"duration"=>$this->duration,
            "recur_type"=>$this->recur_type,
            "recur_data"=>$this->recur_data,
            "end"=>$this->end_date
        ];
        DBHelper::Update("calendar_recurring_events", $update, ['id'=>$this->id]);
        
    }
    
    public static function Create($title, $description, $category,$year,$month,$day, $hour,$minute, $duration,$recur_type,$recur_data, $end_date=0)
    {
        $uid=EngineCore::$CurrentUser->userid;
        $row = [
            null,
            $title,$description,$category,
            $day,$month,$year,$hour,$minute,$duration,
            $end_date,
            $recur_type,$recur_data,
            $uid,0,1
        ];
        DBHelper::Insert("calendar_recurring_events",$row);
        $id = DBHelper::GetLastId();
        $r = new RecurringEvent($id, $title, $description, $category, $year,$month,$day, $hour,$minute, $duration, $recur_type,$recur_data,$end_date);
        return $r;
        
    }
    
    public static function cmp($a,$b)
    {
        if($a==="")
        {
            return -1;
        }
        $l=min(strlen($a),strlen($b));
        for($i=0;$i<$l;$i++)
        {
            if($a[$i]<$b[$i])
                return -1;
            if($a[$i]>$b[$i])
                return 1;
        }
        return 0;
    }
    
    
    public static function CheckMonth($y, $m)
    {
        $output = [];
        $selector = [
            "id",
            "title", "description","category",
            "day","month","year",
            "hour","minute", "duration",
            "end","recur_type","recur_data"
            ];
        $q_recurrers = DBHelper::Select("calendar_recurring_events",$selector,["active"=>1]);
        $q_exceptions = DBHelper::Select("calendar_exceptions",["recurrer_id","day","month","year"],["year"=>$y,"month"=>$m]);
        $recurrers = DBHelper::RunTable($q_recurrers,[1]);
        $exceptions = DBHelper::RunTable($q_exceptions,[$y,$m]);
        $exceptions_by_day = [];
        foreach($exceptions as $ex)
        {
            $eid = $ex['recurrer_id'];
            if(!isset($exceptions_by_day[$ex['day']]))
            {
                $exceptions_by_day[$ex['day']] = [];
            }
            $exceptions_by_day[intval($ex['day'])][]=$eid;
        }
        
        $lateststring = "1970-01-01";
        $pre = str_pad($y,4,"0", STR_PAD_LEFT) . "-". str_pad($m,2,"0", STR_PAD_LEFT);
            
        $frist = new DateTimeImmutable($pre."-01");
        $days = intval($frist->format("t"));
        for($d=1;$d<=$days;$d++)
        {
            $datestring =$pre."-".str_pad($d,2,"0", STR_PAD_LEFT);
            $date = new DateTimeImmutable($datestring);
            foreach($recurrers as $value)
            {
                if(isset($exceptions_by_day[$d]) && in_array($value['id'],$exceptions_by_day[$d]))
                {
                    continue;
                }
                if($value['year']==0)
                {
                    $start = new DateTimeImmutable($lateststring);
                }
                else
                {
                    $start = new DateTimeImmutable($value['year']."-".$value['month']."-".$value['day']);
                }
                if(self::CheckDay($date,$start,$value['end'],$value["recur_type"],$value["recur_data"]))
                {
                    $output[]=CalendarEvent::PrepareForDisplay($value, $y, $m, $d);
                }
            }
        }
        return $output;
        
    }
    
    public static function CheckDate($datestring)
    {
        list($y,$m,$d) = explode("-",$datestring);
        $output = [];
        $selector = [
            "id",
            "title", "description","category",
            "day","month","year",
            "hour","minute", "duration",
            "end","recur_type","recur_data"
            ];
        $q_recurrers = DBHelper::Select("calendar_recurring_events",$selector,["active"=>1]);
        $q_exceptions = DBHelper::Select("calendar_exceptions",["recurrer_id"],["year"=>$y,"month"=>$m,"day"=>$d]);
        $recurrers = DBHelper::RunTable($q_recurrers,[1]);
        $exceptions_by_day = DBHelper::RunList($q_exceptions,[$y,$m,$d]);
            
        $lateststring = "1970-01-01";
       
        $date = new DateTimeImmutable($datestring);
        foreach($recurrers as $value)
        {
            
            if(in_array($value['id'],$exceptions_by_day))
            {
                continue;
            }
            if($value['year']==0)
            {
                $start = new DateTimeImmutable($lateststring);
            }
            else
            {
            $start = new DateTimeImmutable($value['year']."-".$value['month']."-".$value['day']);
            }
            $value['recurId']=$value['id'];
            if(self::CheckDay($date,$start,$value['end'],$value["recur_type"],$value["recur_data"]))
            {
                $output[]=CalendarEvent::PrepareForDisplay($value, $y, $m, $d);
            }
        }
        
        return $output;
        
    }
    
    public static function CheckDay($date,$start_date,$end_date,$recur_type,$recur_data)
    {
        //var_dump([$date,$start_date,$end_date,$recur_type,$recur_data]);
        if($end_date!=0)
        {
            $end = new DateTimeImmutable("@".$end_date);
            $diff=date_diff($date,$end);
            if($diff->invert)
            {
                return false;
            }
            
        }
        $diff =date_diff($start_date,$date);
        if($diff->invert)
        {
            return false;
        }
        switch($recur_type)
        {
            case self::RECUR_DAYS:
            {
                return self::CalculateThisInterval($date,$diff->days,$recur_data);
            }
            case self::RECUR_WEEKLY:
            {
                return self::CalculateThisWeekly($date,$recur_data);
            }
            case self::RECUR_MONTH:
            {
                return $date->format("j") == $recur_data;
            }
        }
    }
    
    public static function CalculateThisInterval($date, $days, $recur_data)
    {
        //var_dump([$date,$days,$recur_data,intval($days) % intval($recur_data)]);
        return intval($days) % intval($recur_data) == 0;
    }
    
    public static function CalculateThisWeekly($date, $recur_data)
    {
        // simply check if current day is marked
        $now = $date->format("N");
        return $recur_data[$now-1] =="*";
        
    }
    
    
    public static function FromEvent($event,$rtype,$rdata)
    {
        $rec = RecurringEvent::Create(
                $event->title,$event->description,$event->type,
                $event->year,$event->month,$event->day,
                $event->hour,$event->minute,$event->duration,
                $rtype,$rdata,0);
        return  $rec;
    }
    
    public function AddException($date)
    {
        list($y,$m,$d) = CalendarEvent::SplitDate($date);
        $new_row = [null,$this->id,$d,$m,$y];
        DBHelper::Insert("calendar_exceptions",$new_row);
    }
    
    public function CreateOnDate($date)
    {
        list($y,$m,$d)=CalendarEvent::SplitDate($date);
        $event = CalendarEvent::Create($this->title,$this->description,$this->category,$y,$m,$d,$this->hour,$this->minute,$this->duration);
        return $event;
    }
    public function Cancel()
    {
        
    }
}
