<?php

namespace Controllers;

use Common\DBHelper;
use Cores\EngineCore;
use Cores\TemplateProcessor;
use DateInterval;
use DateTime;
use Models\Calendar\Event;
use Models\Calendar\RecurringEvent;
use Models\Calendar\Scheduler;
use Route;

/**
 * Description of CalendarController
 *
 */
class CalendarController
{
    #[Route('calendar/view/month','calendar.view')]
    public static function ShowMonth($year = 0, $month = 0)
    {
        EngineCore::StartLap();
        $m = intval($month);
        $y = intval($year);
        
        $recurrings= RecurringEvent::CheckMonth($y,$m);
        $all_events = Scheduler::CheckMonth($y,$m);
        
        $m2=$m+1;
        $y2=$y;
        if($m2>12)
        {
            $m2=1;
            $y2++;
        }
        
        $next_month_events =Scheduler::CheckMonth($y2,$m2);

        // get event type tags and highlights
        $q_mapping = DBHelper::Select(Event::TABLE_TYPES,["id","number_colour","marker_colour","agenda_colour","bg_colour","priority","ghost"],[]);
        $mapping_result = DBHelper::RunTable($q_mapping,[]);
        $mapping=[];
        foreach($mapping_result as $result)
        {
            $mapping[$result['id']]=$result;
        }
        $default=array_keys($mapping)[0];

        //END get event type tags and highlights


        EngineCore::Lap2Debug("got recurrers and this month's events");
        EngineCore::Lap2Debug("prep work");
        $e = ['entity_type'=>'calendar/month'];
        $e['month'] = $m;
        $e['year'] = $y;
        EngineCore::Lap2Debug("got all events");
        EngineCore::Lap2Debug("got all prev events");
        $events_by_day = [];
        foreach($all_events as $id=>$event)
        {
           $c_d = (int) $event['day'];
           if(!isset($events_by_day[$c_d]))
           {
               $events_by_day[$c_d]=[];
           }
           $events_by_day[$c_d][]=$event;
           
        }
        EngineCore::Lap2Debug("done processing month events");
        foreach($recurrings as $event)
        {
           $c_d = (int) $event['day'];
           if(!isset($events_by_day[$c_d]))
           {
               $events_by_day[$c_d]=[];
           }
           $events_by_day[$c_d][]=$event;
        }
        EngineCore::Lap2Debug("done processing recurrings");
        
        EngineCore::Lap2Debug("filled upcoming");
        $e['events'] = $events_by_day;
        $e['markers'] = $mapping;
        $e['next_month'] = $next_month_events;
        return $e;
        
    }
}
