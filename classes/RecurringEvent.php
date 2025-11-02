<?php



Module::DemandProperty("calendar.recurring.type", "Duration", "The duration of an event.");
Module::DemandProperty("calendar.recurring.start_date", "Start date", "The starting date of a recurring event.");
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
    public $time;
    public $duration;
    
    public const RECUR_WEEKLY = "week";
    public const RECUR_DAYS = "day";
    public const RECUR_MONTH = "month";
    
    public function __construct($id,$recur_type,$recur_data, $title, $description,$startdate, $time, $duration, $event_type)
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
        $eva->SetSingleAttribute('calendar.time',$this->time);
        $eva->SetSingleAttribute('calendar.duration',$this->duration);
        $eva->SetSingleAttribute('title',$this->title);
        $eva->SetSingleAttribute('description',$this->description);
        $eva->SetSingleAttribute('calendar.event_type',$this->event_type);
        $eva->Save();
        
    }
    
    public static function Create($recur_type,$recur_data, $title, $description,$startdate, $time, $duration, $event_type)
    {
        $id = EVA::CreateObject("calendar.recurring");
        $r = new RecurringEvent($id->id, $recur_type,$recur_data, $title, $description,$startdate, $time, $duration, $event_type);
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
        $recurrers = EVA::GetAsTable(
                ["calendar.recurring.type",
                    "calendar.recurring.data",
                    "title","description",
                    "calendar.time",
                    "calendar.duration",
                    "calendar.event_type",
                    "calendar.recurring.start_date"
                    ], 
                "calendar.recurring");
        $lateststring = "1970-01-01";
        $pre = str_pad($y,4,"0", STR_PAD_LEFT) . "-". str_pad($m,2,"0", STR_PAD_LEFT);
        $exceptionsList = EVA::GetByPropertyPre("calendar.date", $pre, "calendar.exception");
        $exceptions = [];
        $exceptions_by_day = [];
        if(count($exceptionsList)>0)
        {
            $exceptions = EVA::GetAsTable(["calendar.date","calendar.event.parent"],"calendar.exception",$exceptionsList);
        }
        foreach($exceptions as $id=>$ex)
        {
            $eid = $ex['calendar.event.parent'];
            $eday = substr($ex['calendar.date'],8,2);
            if(!isset($exceptions_by_day[$eday]))
            {
                $exceptions_by_day[$eday] = [];
            }
            $exceptions_by_day[intval($eday)][]=$eid;
        }
        
            
        $frist = new DateTimeImmutable($pre."-01");
        $days = intval($frist->format("t"));
        for($d=1;$d<=$days;$d++)
        {
            $datestring =$pre."-".str_pad($d,2,"0", STR_PAD_LEFT);
            $date = new DateTimeImmutable($datestring);
            foreach($recurrers as $id=>$value)
            {
                $startstring = $value["calendar.recurring.start_date"] == '' ? $lateststring : $value["calendar.recurring.start_date"];
                $start = new DateTimeImmutable($startstring);
                if(!(isset($exceptions_by_day[$d]) && in_array($id,$exceptions_by_day[$d]))
                    &&    self::CheckDay($date,$start,$value["calendar.recurring.type"],$value["calendar.recurring.data"]))
                {
                    $value['calendar.date'] = $datestring;
                    $value['recurrer'] = $id;
                    $output[]=$value;
                }
            }
        }
        return $output;
        
    }
    
    public static function CheckDate($datestring)
    {
        $output = [];
        $recurrers = EVA::GetAsTable(
                ["calendar.recurring.type",
                    "calendar.recurring.data",
                    "title","description",
                    "calendar.time",
                    "calendar.duration",
                    "calendar.event_type",
                    "calendar.recurring.start_date"
                    ], 
                "calendar.recurring");
        $exceptionsList = EVA::GetByProperty("calendar.date", $datestring, "calendar.exception");
        $exceptions = [];
        $exceptions_by_day = [];
        if(count($exceptionsList)>0)
        {
            $exceptions = EVA::GetAsTable(["calendar.event.parent"],"calendar.exception",$exceptionsList);
        }
        foreach($exceptions as $id=>$ex)
        {
            $eid = $ex['calendar.event.parent'];
            $exceptions_by_day[]=$eid;
        }
            
       
        $date = new DateTimeImmutable($datestring);
        foreach($recurrers as $id=>$value)
        {
            $start = $value["calendar.recurring.start_date"] == '' ? new DateTimeImmutable("1970-01-01") : new DateTimeImmutable($value["calendar.recurring.start_date"]);
            if(!in_array($id,$exceptions_by_day)
                &&    self::CheckDay($date,$start,$value["calendar.recurring.type"],$value["calendar.recurring.data"]))
            {
                $value['calendar.date'] = $datestring;
                $value['recurId'] = $id;
                $output[]=$value;
            }
        }
        
        return $output;
        
    }
    
    public static function CheckDay($date,$start_date,$recur_type,$recur_data)
    {
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
