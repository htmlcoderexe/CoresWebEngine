<?php

class CalendarScheduler
{
    public static function CheckDate($y,$m="",$d="")
    {
        
        $fields=[
            "id",
            "title", "description","category",
            "day","month","year",
            "hour","minute", "duration"
            ];
        $q_events = DBHelper::Select("calendar_events",$fields,["year"=>$y,"month"=>$m,"day"=>$d]);
        $events = DBHelper::RunTable($q_events,[$y,$m,$d]);
        $output =[];
        foreach($events as $value)
        {
            $value['day']=str_pad($value['day'],2,"0", STR_PAD_LEFT);
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
        return  $output;
        
        
        
    }
    
    public static function GetExceptions($y,$m)
    {
        $m=str_pad($m,2,"0", STR_PAD_LEFT);
        $eids=EVA::GetByPropertyPre("calendar.date", $y."-".$m."-", "calendar.exception");
        if(!$eids)
        {
            return [];
        }
        $exceptions = EVA::GetAsTable(["calendar.event.parent","calendar.date"], "calendar.exception",$eids);
        return $exceptions;
    }
    
    public static function SortByDateTime($items)
    {
        $sorter = function($a, $b)
        {
            $props = [
                'year',
                'month',
                'day',
                'hour',
                'minute'
            ];
            foreach($props as $prop)
            {
                if($a[$prop]==$b[$prop])
                    continue;
                return $a[$prop]<$b[$prop]?-1:1;
            }
            return 0;
        };
        usort($items,$sorter);
        return $items;
    }
    
    public static function HHMM2Minutes($hhmm)
    {
        $hh=intval(substr($hhmm,0,2));
        $mm=intval(substr($hhmm,3,2));
        return $hh*60+$mm;
    }
    
    public static function TestOverlap($a,$b)
    {
        return ($a['dayminute'] < $b['doneminute'] && $a['doneminute'] > $b['dayminute']);
    }
    
    public static function CheckMonth($y, $m)
    {
        $fields=[
            "id",
            "title", "description","category",
            "day","month","year",
            "hour","minute", "duration"
            ];
        $q_events = DBHelper::Select("calendar_events",$fields,["year"=>$y,"month"=>$m]);
        $events = DBHelper::RunTable($q_events,[$y,$m]);
        $output =[];
        foreach($events as $value)
        {
            $value['day']=str_pad($value['day'],2,"0", STR_PAD_LEFT);
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
        return  $output;
    }
    
    
    
    public static function CheckRecurrers($y,$m,$d)
    {
        
    }
}
