<?php

class CalendarScheduler
{
    public static function CheckDate($y,$m,$d)
    {
        self::CheckRecurrers($y,$m,$d);
        
        $dates = EVA::GetByProperty("calendar.date", $y."-".$m."-".$d, "calendar.event");
        
        return $dates;
        
    }
    
    public static function CheckRecurrers($y,$m,$d)
    {
        
    }
}
