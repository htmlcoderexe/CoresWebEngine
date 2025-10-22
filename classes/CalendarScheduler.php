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
    
    public static function CheckMonth($y, $m)
    {
        return EVA::GetByPropertyPre("calendar.date", $y."-".$m."-", "calendar.event");
    }
    
    
    
    public static function CheckRecurrers($y,$m,$d)
    {
        
    }
}
