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
    
    
    
    public static function CheckRecurrers($y,$m,$d)
    {
        
    }
}
