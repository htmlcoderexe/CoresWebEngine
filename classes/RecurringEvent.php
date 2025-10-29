<?php



Module::DemandProperty("calendar.recurring.type", "Duration", "The duration of an event.");
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
    public $latest_id;
    public $latest_date;
    public $time;
    public $duration;
    
    public const RECUR_WEEKLY = "week";
    public const RECUR_DAYS = "day";
    public const RECUR_MONTH = "month";
    
    public function __construct($id,$recur_type,$recur_data, $title, $description, $time, $duration, $event_type, $latest_id, $latest_date)
    {
        $this->id=$id;
        $this->recur_type = $recur_type;
        $this->recur_data = $recur_data;
        $this->title = $title;
        $this->description = $description;
        $this->time = $time;
        $this->duration = $duration;
        $this->event_type = $event_type;
        $this->latest_id = $latest_id;
        $this->latest_date = $latest_date;
                
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
                $eva->attributes['calendar.time'],
                $eva->attributes['calendar.duration'],
                $eva->attributes['calendar.event_type'],
                $eva->attributes['calendar.recurring.latest_id'],
                $eva->attributes['calendar.recurring.latest_date']);
        
    }
    
    public function Save()
    {
        $eva = new EVA($this->id);
        $eva->SetSingleAttribute('calendar.recurring.type',$this->recur_type);
        $eva->SetSingleAttribute('calendar.recurring.data',$this->recur_data);
        $eva->SetSingleAttribute('calendar.time',$this->time);
        $eva->SetSingleAttribute('calendar.duration',$this->duration);
        $eva->SetSingleAttribute('title',$this->title);
        $eva->SetSingleAttribute('description',$this->description);
        $eva->SetSingleAttribute('calendar.event_type',$this->event_type);
        $eva->SetSingleAttribute('calendar.recurring.latest_id',$this->latest_id);
        $eva->SetSingleAttribute('calendar.recurring.latest_date',$this->latest_date);
        $eva->Save();
        
    }
    
    public static function Create($recur_type,$recur_data, $title, $description, $time, $duration, $event_type)
    {
        $id = EVA::CreateObject("calendar.recurring");
        $r = new RecurringEvent($id->id, $recur_type,$recur_data, $title, $description, $time, $duration, $event_type,"","");
        $r->Save();
        return $r;
        
    }
    
    public static function Refresh()
    {
        $recurrers = EVA::GetAsTable(
                ["calendar.recurring.type",
                    "calendar.recurring.data",
                    "title","description",
                    "calendar.time",
                    "calendar.duration",
                    "calendar.event_type",
                    "calendar.recurring.latest_id",
                    "calendar.recurring.latest_date"
                    ], 
                "calendar.recurring");
        $now = time();
        foreach($recurrers as $id=>$data)
        {
            $latest = $data["calendar.recurring.latest_date"];
            $y=substr($latest,0,4);
            $m=substr($latest,5,2);
            $d=substr($latest,8,2);
            $last_time = mktime(0,0,0,$m,$d,$y);
            if($last_time<$now)
            {
                $generator = self::Load($id);
                $generator->CreateNext();
            }
        }
    }
    
    public static function CalculateNextMonthly($date,$recur_data)
    {
        $newDate = $date->modify("+1 month");
        return $newDate;
    }
    
    public static function CalculateNextInterval($date, $recur_data)
    {
        $newDate = $date->modify("+".$recur_data." days");
        return $newDate;
    }
    
    public static function CalculateNextWeekly($date, $recur_data)
    {
        // no point in first getting this, (monday leading)
        // then "converting" to 0-based (monday leading)
        // THEN adding a 1 for next day as the start
        $now = $date->format("N");
        $timeline = $recur_data.$recur_data;
        for($i=1;$i<8;$i++)
        {
            // recur_data for weeklies looks like this:
            // "** **  " - this is every Monday, Tuesday, Thursday and Friday
            if($timeline[$i+$now-1] == "*")
            {
                return $date->modify("+".$i." days");
            }
            
        }
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
                    "calendar.recurring.latest_id",
                    "calendar.recurring.latest_date"
                    ], 
                "calendar.recurring");
        $lateststring = "";
        $latest = new DateTimeImmutable($lateststring);
            
        $pre = str_pad($y,4,"0", STR_PAD_LEFT) . "-". str_pad($m,2,"0", STR_PAD_LEFT);
        $frist = new DateTimeImmutable($pre."-01");
        $days = intval($frist->format("t"));
        for($d=1;$d<=$days;$d++)
        {
            $datestring =$pre."-".str_pad($d,2,"0", STR_PAD_LEFT);
            $date = new DateTimeImmutable($datestring);
            foreach($recurrers as $id=>$value)
            {
                if(self::CheckDate2($date,$latest,$value["calendar.recurring.type"],$value["calendar.recurring.data"]))
                {
                    $value['calendar.date'] = $datestring;
                    $output[]=$value;
                }
            }
        }
        return $output;
        
    }
    
    public static function CheckDate2($date,$latest,$recur_type,$recur_data)
    {
        $diff =date_diff($latest,$date);
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
                return $date->format("j" == $recur_data);
            }
        }
    }
    
    
    public static function DoMonth($y,$m)
    {
        // EngineCore::Write2Debug("|".$y."-".$m."|");
        $recurrers = EVA::GetAsTable(
                ["calendar.recurring.type",
                    "calendar.recurring.data",
                    "title","description",
                    "calendar.time",
                    "calendar.duration",
                    "calendar.event_type",
                    "calendar.recurring.latest_id",
                    "calendar.recurring.latest_date"
                    ], 
                "calendar.recurring");
        
        $all_event_ids= CalendarScheduler::CheckMonth(intval($y),intval($m));
        $all_events = [];
        if($all_event_ids)
        {
            $all_events = EVA::GetAsTable(["calendar.date","calendar.event.parent"], "calendar.event",$all_event_ids);
        }
        $daymap=[];
        foreach($all_events as $id=>$data)
        {
            if($data['calendar.event.parent']=="")
            {
                continue;
            }
            $day = intval(substr($data['calendar.date'],8,2));
            if(!isset($daymap[$day]))
            {
                $daymap[$day]=[];
            }
            $daymap[$day][]=intval($data['calendar.event.parent']);
        }
        $pre = str_pad($y,4,"0", STR_PAD_LEFT) . "-". str_pad($m,2,"0", STR_PAD_LEFT);
        $frist = new DateTimeImmutable($pre."-01");
        $days = intval($frist->format("t"));
        //var_dump($recurrers);
        for($d=$days;$d>0;$d--)
        {
            //echo($d);
            $curr = $pre."-".str_pad($d,2,"0", STR_PAD_LEFT);
            foreach($recurrers as $id=>$value)
            {
                //EngineCore::Write2Debug("fuck");
                // if recurrer already exists here
                if(isset($daymap[$d]) && in_array($id,$daymap[$d]))
                {
                    continue;
                }
                //EngineCore::Write2Debug("ass");
                // if recurrer has latest in future
                if(self::cmp($value['calendar.recurring.latest_date'],$curr)>=0)
                {
                    unset($recurrers[$id]);
                    continue;
                }
                //EngineCore::Write2Debug("shit");
                // only then check if should be created
                if(self::CheckDate($curr,$value['calendar.recurring.latest_date'],$value["calendar.recurring.type"],$value["calendar.recurring.data"]))
                {
                    // do shit here
                    
                    $event = EVA::CreateObject("calendar.event");
                    $event->AddAttribute("title",$value["title"]);
                    $event->AddAttribute("calendar.date",$curr);
                    if(!isset($value["calendar.time"]))
                    {
                        $value["calendar.time"] ="00:00";
                    }
                    if(!isset($value["calendar.duration"]))
                    {
                        $value["calendar.duration"]="01:00";
                    }
                    $event->AddAttribute("calendar.time",$value["calendar.time"]);
                    $event->AddAttribute("calendar.duration",$value["calendar.duration"]);
                    $event->AddAttribute("description",$value["description"]);
                    $event->AddAttribute("calendar.event_type",$value["calendar.event_type"]);
                    $event->AddAttribute("calendar.event.parent",$id);
                    $event->Save();
                    // IMPORTANT!!!! this updates DB but not the current recurrer list in here - so it can
                    // fill the "earlier" unfilled dates yet if it happens
                    // YOU WILL BREAK THE WHOLE THING IF YOU CHANGE THIS
                    // actually fuck that don't write it because it will break the recurring event if you click the months wrong
                    // EVA::WritePropByName($id, "calendar.recurring.latest_date", $curr);
                }
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
    
    public static function CalculateNext($datestring,$recur_type,$recur_data)
    {
        $date = new DateTimeImmutable($datestring);
        switch($recur_type)
        {
            case self::RECUR_DAYS:
            {
                return self::CalculateNextInterval($date,$recur_data);
            }
            case self::RECUR_WEEKLY:
            {
                return self::CalculateNextWeekly($date,$recur_data);
            }
            case self::RECUR_MONTH:
            {
                return self::CalculateNextMonthly($date,$recur_data);
            }
        }
    }
    
    public static function CheckDate($datestring,$lateststring,$recur_type,$recur_data)
    {
        //EngineCore::Dump2Debug([$datestring,$lateststring,$recur_type,$recur_data]);
        if($datestring == $lateststring)
        {
            return false;
        }
        $date = new DateTimeImmutable($datestring);
        $latest = new DateTimeImmutable($lateststring);
        $diff =date_diff($latest,$date);
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
                return $date->format("j" == $recur_data);
            }
        }
    }
    
    public static function FromEvent($event)
    {
        
    }
    
    public function CreateNext()
    {
        
    }
    public function Cancel()
    {
        
    }
}
