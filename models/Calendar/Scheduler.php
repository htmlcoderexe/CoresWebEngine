<?php
namespace Models\Calendar;

use Common\DBHelper;

class Scheduler
{
    public static function CheckDate($y,$m,$d)
    {
        
        $fields=[
            "id",
            "title", "description","category",
            "day","month","year",
            "hour","minute", "duration"
            ];
        $q_events = DBHelper::Select(Event::TABLE,$fields,["year"=>$y,"month"=>$m,"day"=>$d, 'active'=>1]);
        $events = DBHelper::RunTable($q_events,[$y,$m,$d,1]);
        $output =[];
        foreach($events as $value)
        {
            $output[]=$value;//Event::PrepareForDisplay($value, $y, $m, $d);
        }
        return  $output;
        
        
        
    }
    

    
    public static function CheckMonth($y, $m)
    {
        $fields=[
            "id",
            "title", "description","category",
            "day","month","year",
            "hour","minute", "duration"
            ];
        $q_events = DBHelper::Select(Event::TABLE,$fields,["year"=>$y,"month"=>$m, 'active'=>1]);
        $events = DBHelper::RunTable($q_events,[$y,$m,1]);
        $output =[];
        foreach($events as $value)
        {
            $output[]=$value;//Event::PrepareForDisplay($value, $y, $m, $value['day']);
        }
        return  $output;
    }
    
}
