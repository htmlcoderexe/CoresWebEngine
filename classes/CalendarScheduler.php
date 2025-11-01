<?php

class CalendarScheduler
{
    public static function CheckDate($y,$m="",$d="")
    {
        self::CheckRecurrers($y,$m,$d);
        if($m=="")
        {
            $dates = EVA::GetByProperty("calendar.date", $y, "calendar.event");
        
        }
        else
        {
            $dates = EVA::GetByProperty("calendar.date", $y."-".$m."-".$d, "calendar.event");
        
        }
        
        return $dates;
        
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
            $ad = $a['calendar.date'];
            $bd = $b['calendar.date'];
            $d=strcmp($ad,$bd);
            if($d<0)
                return -1;
            if($d>0)
                return 1;
            $at = $a['calendar.time'];
            $bt = $b['calendar.time'];
            $t=strcmp($at,$bt);
            if($t<0)
                return -1;
            if($t>0)
                return 1;
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
        $as = self::HHMM2Minutes($a['calendar.time']);
        $ae = $as+self::HHMM2Minutes($a['calendar.duration']);
        $bs = self::HHMM2Minutes($b['calendar.time']);
        $be = $bs+self::HHMM2Minutes($b['calendar.duration']);
        return ($as < $be && $ae > $bs);
    }
    
    public static function CheckMonth($y, $m)
    {
        $m=str_pad($m,2,"0", STR_PAD_LEFT);
        return EVA::GetByPropertyPre("calendar.date", $y."-".$m."-", "calendar.event");
    }
    
    
    
    public static function CheckRecurrers($y,$m,$d)
    {
        
    }
}
