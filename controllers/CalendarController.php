<?php

namespace Controllers;

use Common\DBHelper;
use Cores\EngineCore;
use Models\Calendar\Event;
use Models\Calendar\RecurringEvent;
use Models\Calendar\Scheduler;
use PostRoute;
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
        EngineCore::AddScript('/js/calendar/renderer.js');
        EngineCore::AddStyle('/css/calendar/main.css');
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

        //END get event type tags and highlights
        $e = ['entity_type'=>'calendar/month'];
        $e['month'] = $m;
        $e['year'] = $y;
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
        foreach($recurrings as $event)
        {
           $c_d = (int) $event['day'];
           if(!isset($events_by_day[$c_d]))
           {
               $events_by_day[$c_d]=[];
           }
           $events_by_day[$c_d][]=$event;
        }
        $e['events'] = $events_by_day;
        $e['markers'] = $mapping;
        $e['next_month'] = $next_month_events;
        return $e;
        
    }
    
    #[Route('calendar/view/week','calendar.view')]
    public static function ShowWeek($year = 0, $week = 0)
    {
        EngineCore::AddScript('/js/calendar/renderer.js');
        EngineCore::AddStyle('/css/calendar/main.css');
        $events_per_day =[];
        $e = ['entity_type'=>'calendar/week',
            'styles' => Event::GetEventTypes(),
            'year' => $year,
            'week'=>$week
        ];
    
        for($i =1;$i<8;$i++)
        {
            $date = strtotime($year."W".sprintf("%02u", $week).$i);


            $eventsThisDay = Scheduler::CheckDate(date("Y",$date),date("n",$date),date("j",$date));
            $recurs = RecurringEvent::CheckDate(date("Y-m-d",$date));
            $events_per_day[$i] = array_merge($recurs,$eventsThisDay);
        }
        $e['events'] = $events_per_day;
        
        return $e;
    }
    
    #[Route('calendar/view/date','calendar.view')]
    public static function ShowDay($year = 0, $month = 0, $day = 0)
    {
        EngineCore::AddScript('/js/calendar/renderer.js');
        EngineCore::AddStyle('/css/calendar/main.css');
        $y = intval($year);
        $m = intval($month);
        $d = intval($day);
        $events= Scheduler::CheckDate($y,$m,$d);
        $datestring = sprintf("%04d-%02d-%02d",$y,$m,$d);
        $recurs = RecurringEvent::CheckDate($datestring);
        $events = array_merge($events,$recurs);
        $e =['entity_type'=> 'calendar/date',
            'events'=>$events];
        return $e;
    }
    
    #[Route('calendar/view/event','calendar.view')]
    public static function ShowEvent($id = 0)
    {
        EngineCore::AddScript('/js/calendar/renderer.js');
        EngineCore::AddStyle('/css/calendar/main.css');
       
        $e = Event::Load(intval($id));
        if(!$e)
        {
            return EngineCore::Error(404, "Event not found");
        }
        
        EngineCore::SetPageTitle("Event on {$e->day}-{$e->month}-{$e->year}");
        $e = (array)$e;
        $e['entity_type'] = 'calendar/event';
        $e['event'] = $e;
        return $e;
    }
    
    #[Route('calendar/edit','calendar.edit')]
    public static function ShowEventEditor($id = 0)
    {
        EngineCore::AddScript('/js/calendar/renderer.js');
        EngineCore::AddStyle('/css/calendar/main.css');
        $e = Event::Load(intval($id));
        if(!$e)
        {
            return EngineCore::Error(404, "Event not found");
        }
        $e = (array)$e;
        $e['entity_type'] = 'calendar/editevent';
        $e['types'] = Event::GetEventTypes(true);
        $e['verb']='save';
        return $e;
    }
    #[Route('calendar/create','calendar.edit')]
    public static function CreateOn($year=1970, $month=01, $day=01)
    {
        EngineCore::AddScript('/js/calendar/renderer.js');
        EngineCore::AddStyle('/css/calendar/main.css');
        return ['entity_type'=>'calendar/editevent',
            'year'=>intval($year),
            'month'=>intval($month),
            'day'=>intval($day),
            'types'=>Event::GetEventTypes(true),
            'verb'=>'save'
            ];
    }
    #[PostRoute('calendar/save','calendar.edit')]
    public static function CreateOrUpdateEvent()
    {        
        $title = EngineCore::POST("title","<untitled>");
        $date = EngineCore::POST("date","1970-01-01");
        $time = EngineCore::POST("time","00:00");
        $sduration = EngineCore::POST("timeD","01:00");
        $description = EngineCore::POST("description","");
        $eventId=intval(EngineCore::POST("EventID"));
        $type=EngineCore::Post("type","");
        list($y,$m,$d) = explode("-",$date);
        list($h,$min) = explode(":", $time);
        list($dh, $dm) = explode(":", $sduration);
        $duration = $dh*60+$dm;
        
        if($eventId == -1)
        {
            // create
            $event = Event::Create(
                    year:  $y,
                    month:  $m,
                    day:  $d,
                    hour:  $h,
                    minute:  $min,
                    title:  $title,
                    description:  $description,
                    category:  $type,
                    duration:  $duration
            );
        }
        else
        {
            // update
            $event = Event::Load($eventId);
            if(!$event)
            {
                return EngineCore::Error(404, "Event does not exist.");
            }
            $event->title = $title;
            $event->hour = $h;
            $event->minute = $min;
            $event->duration = $duration;
            $event->year=$y;
            $event->month = $m;
            $event->day = $d;
            $event->description = $description;
            $event->type = $type;
            $event->Save();
        }
        EngineCore::GTFO("/calendar/view/event/".$event->id);
    }
    
    #[PostRoute('calendar/delete','calendar.edit')]
    public static function RemoveEvent()
    {
        $id=EngineCore::POST("id_to_delete","-1");
        $e = CalendarEvent::Load($id);
        if($e)
        {
            $e->Deactivate();
        }
        $returnTo="";
        if(isset($_SESSION['returnTo']))
        {
            $returnTo=$_SESSION['returnTo'];
            $_SESSION['returnTo']="";
        }
        EngineCore::GTFO("/calendar".$returnTo);
    }
    
    
    
}
