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
    public $recur_type;
    public $recur_data;
    public $title;
    public $description;
    public $event_type;
    public $start_date;
    public $end_date;
    public $time;
    public $duration;
    
    public const RECUR_WEEKLY = "week";
    public const RECUR_DAYS = "day";
    public const RECUR_MONTH = "month";
    
    public function __construct($id,$recur_type,$recur_data, $title, $description,$startdate, $time, $duration, $event_type,$enddate="")
    {
        $this->id=$id;
        $this->recur_type = $recur_type;
        $this->recur_data = $recur_data;
        $this->title = $title;
        $this->description = $description;
        $this->start_date = $startdate;
        $this->time = $time;
        $this->duration = $duration;
        $this->event_type = $event_type;
        $this->end_date = $enddate; 
                
    }
    
    public static function Load($id)
    {
        $eva = new EVA($id);
        if(!$eva)
        {
            return null;
        }
        return new RecurringEvent($id, 
                $eva->attributes['calendar.recurring.type'],
                $eva->attributes['calendar.recurring.data'],
                $eva->attributes['title'],
                $eva->attributes['description'],
                $eva->atrributes["calendar.recurring.start_date"] ?? '1970-01-01',
                $eva->atrributes["calendar.recurring.end_date"] ?? '',
                $eva->attributes['calendar.time'] ?? '00:00',
                $eva->attributes['calendar.duration'] ?? '01:00',
                $eva->attributes['calendar.event_type']);
        
    }
    
    public function Save()
    {
        $eva = new EVA($this->id);
        $eva->SetSingleAttribute('calendar.recurring.type',$this->recur_type);
        $eva->SetSingleAttribute('calendar.recurring.data',$this->recur_data);
        $eva->SetSingleAttribute("calendar.recurring.start_date", $this->start_date);
        $eva->SetSingleAttribute("calendar.recurring.end_date", $this->end_date);
        $eva->SetSingleAttribute('calendar.time',$this->time);
        $eva->SetSingleAttribute('calendar.duration',$this->duration);
        $eva->SetSingleAttribute('title',$this->title);
        $eva->SetSingleAttribute('description',$this->description);
        $eva->SetSingleAttribute('calendar.event_type',$this->event_type);
        $eva->Save();
        
    }
    
    public static function Create($recur_type,$recur_data, $title, $description,$startdate, $time, $duration, $event_type,$end_date="")
    {
        $id = EVA::CreateObject("calendar.recurring");
        $r = new RecurringEvent($id->id, $recur_type,$recur_data, $title, $description,$startdate, $time, $duration, $event_type,$end_date);
        $r->Save();
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
                    $value['calendar.date'] = $datestring;
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
                    $output[]=$value;
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
            if(self::CheckDay($date,$start,$value['end'],$value["recur_type"],$value["recur_data"]))
            {
                    $value['calendar.date'] = $datestring;
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
                    $value['recurId'] = $value['id'];
                    $value['duration_minutes'] = str_pad($value['duration'] % 60,2,"0", STR_PAD_LEFT);
                    $value['duration_hours'] = str_pad(floor($value['duration'] / 60),2,"0", STR_PAD_LEFT);
                    $value['done_minutes'] = str_pad($value['doneminute'] % 60,2,"0", STR_PAD_LEFT);
                    $value['done_hours'] = str_pad(floor($value['doneminute'] / 60),2,"0", STR_PAD_LEFT);
                    $output[]=$value;
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
        $rec = RecurringEvent::Create($rtype,$rdata,$event->title,$event->description,$event->date,$event->startTime,$event->duration,$event->type);
        return  $rec;
    }
    
    public function AddException($date)
    {
        $exception = EVA::CreateObject("calendar.exception");
        $exception->AddAttribute("calendar.date",$date);
        $exception->AddAttribute("calendar.event.parent",$this->id);
        $exception->Save();
    }
    
    public function CreateOnDate($date)
    {
        $event = EVA::CreateObject("calendar.event");
        $event->AddAttribute("title",$this->title);
        $event->AddAttribute("calendar.date",$date);
        $event->AddAttribute("calendar.time",$this->time);
        $event->AddAttribute("calendar.duration",$this->duration);
        $event->AddAttribute("description",$this->description);
        $event->AddAttribute("calendar.event_type",$this->event_type);
        $event->AddAttribute("calendar.event.parent",$this->id);
        $event->Save();
        return $event;
    }
    public function Cancel()
    {
        
    }
}
