<?php

class CalendarScheduler
{
    public static function CheckDate($y,$m,$d)
    {
        
        $fields=[
            "id",
            "title", "description","category",
            "day","month","year",
            "hour","minute", "duration"
            ];
        $q_events = DBHelper::Select(CALENDAR_EVENTS_TABLE,$fields,["year"=>$y,"month"=>$m,"day"=>$d, 'active'=>1]);
        $events = DBHelper::RunTable($q_events,[$y,$m,$d,1]);
        $output =[];
        foreach($events as $value)
        {
            $output[]=CalendarEvent::PrepareForDisplay($value, $y, $m, $d);
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
        $q_events = DBHelper::Select(CALENDAR_EVENTS_TABLE,$fields,["year"=>$y,"month"=>$m, 'active'=>1]);
        $events = DBHelper::RunTable($q_events,[$y,$m,1]);
        $output =[];
        foreach($events as $value)
        {
            $output[]=CalendarEvent::PrepareForDisplay($value, $y, $m, $value['day']);
        }
        return  $output;
    }
    
    
    
    public static function CheckRecurrers($y,$m,$d)
    {
        
    }
}
