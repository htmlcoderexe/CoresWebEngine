<?php


namespace Controllers;

use Cores\EngineCore;
use Models\Calendar\Event;
use Models\Calendar\RecurringEvent;
use PostRoute;
use Route;

/**
 * Description of CalendarRecurringEventsController
 *
 */
class CalendarRecurringEventsController
{
    
    #[Route('calendar/recurring/view','calendar.view')]
    public static function ShowRecurring($id=0)
    {
        EngineCore::AddScript('/js/calendar/renderer.js');
        EngineCore::AddStyle('/css/calendar/main.css');
        $id=intval($id);
        $r = RecurringEvent::Load($id);
        if(!$r)
        {
            return EngineCore::Error(404, "Recurring event does not exist.");
        }
        
    }
    
    #[Route('calendar/recurring/edit','calendar.edit')]
    public static function ShowRecurrerEditor($id=0)
    {
        EngineCore::AddScript('/js/calendar/renderer.js');
        EngineCore::AddStyle('/css/calendar/main.css');
        $id=intval($id);
        $r = RecurringEvent::Load($id);
        if(!$r)
        {
            return EngineCore::Error(404, "Recurring event does not exist.");
        }
        $e = (array)$r;
        $e['entity_type']='calendar/editrecurring';
        $e['types'] = Event::GetEventTypes(true);
        return $e;
    }
    
    #[PostRoute('calendar/recurring/save','calendar.edit')]
    public static function SaveRecurrerChanges()
    {
        $title = EngineCore::POST("title","<untitled>");
        $date = EngineCore::POST("date","1970-01-01");
        $time = EngineCore::POST("time","00:00");
        $sduration = EngineCore::POST("timeD","01:00");
        $description = EngineCore::POST("description","");
        $eventId=intval(EngineCore::POST("EventID"));
        if(!RecurringEvent::Load($eventId))
        {
            return EngineCore::Error(404, "Recurring event does not exist.");
        }
        $type=EngineCore::Post("type","");
        list($y,$m,$d) = explode("-",$date);
        list($h,$min) = explode(":", $time);
        list($dh, $dm) = explode(":", $sduration);
        $duration = $dh*60+$dm;
        $rtype=EngineCore::Post("rtype","");
        $rdata=EngineCore::Post("rdata","");
        $enddate=0;
        if(EngineCore::POST("end_date_option","no")=="yes")
        {
            $enddate=strtotime(EngineCore::POST("date_end"));
        }
        $event = new RecurringEvent($eventId,$title,$description,$type,$y,$m,$d,$h,$min,$duration,$rtype,$rdata,$enddate);
        $event->Save();
        EngineCore::GTFO("/calendar/recurring/edit/".$event->id);
    }
    
    #[PostRoute('calendar/recurring/from','calendar.edit')]
    public static function CreateRecurrerFromEvent($id= 0)
    {
        $evt = Event::Load(intval($id));
        if(!$evt)
        {
            return EngineCore::Error(404, "Event does not exist.");
        }
        $rtype = EngineCore::POST("rtype", RecurringEvent::RECUR_MONTH);
        $rdata = EngineCore::POST("rdata","1");
        $rec = RecurringEvent::FromEvent($evt, $rtype, $rdata);
        EngineCore::GTFO("/calendar/recurring/view/".$rec->id);
    }
    
    #[PostRoute('calendar/recurring/except','calendar.edit')]
    public static function CreateException($id)
    {
        
        $id=intval($id);
        $recurrer = RecurringEvent::Load($id);
        if(!$recurrer)
        {
            return EngineCore::Error(404, "Recurring event does not exist.");
        }
        $action = EngineCore::POST("action","create");
        $date = EngineCore::POST("date","1970-01-01");
        list($y,$m,$d) = explode("-",$date);
        $recurrer->AddException($date);
        if($action == "create")
        {
            EngineCore::GTFO("/calendar/edit/".($recurrer->CreateOnDate($date))->id);
        }
        EngineCore::GTFO("/calendar/view/date/".$y."/".$m."/".$d);
    }
}
