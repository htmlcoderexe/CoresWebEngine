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
    
    public static function CheckMonth($y, $m)
    {
        $m=str_pad($m,2,"0", STR_PAD_LEFT);
        return EVA::GetByPropertyPre("calendar.date", $y."-".$m."-", "calendar.event");
    }
    
    
    
    public static function CheckRecurrers($y,$m,$d)
    {
        
    }
}
