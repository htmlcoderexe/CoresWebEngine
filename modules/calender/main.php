<?php

function ModuleFunction_calender_ParseYYYYMMDD($params,$split=false)
{
    $result= $params[0].$params[1].$params[2].$params[3]."-".$params[4].$params[5]."-".$params[6].$params[7];
    if(!$split)
    {
        return $result;
    }
    return explode("-",$result);
}

function ModuleAction_calender_default($params)
{
    $now= new DateTime();
    $thismonth = $now->format("Ym");
    ModuleFunction_calender_ShowMonth($thismonth,true);
    
}

function ModuleFunction_CreateEvent($title,$date,$time,$description)
{
    if(!$title || !$date)
    {
        EngineCore::GTFO("/calender/create/error");
        return;
    }
    $event = EVA::CreateObject("calendar.event");
    $event->AddAttribute("title",$title);
    $event->AddAttribute("calendar.date",$date);
    if(!$time)
    {
        $time ="00:00";
    }
    $event->AddAttribute("calendar.time",$time);
    $event->AddAttribute("description",$description);
    $event->Save();
    EngineCore::GTFO("/calender");
    return;
}

function ModuleAction_calender_create($params)
{
    $title = EngineCore::POST("title","");
    $date = EngineCore::POST("date","");
    $time = EngineCore::POST("time","");
    $description = EngineCore::POST("description","");
    $submitted=EngineCore::POST("create","");
    if($submitted)
    {
        ModuleFunction_CreateEvent($title,$date,$time,$description);
        return;
    }
    $mode = isset ($params[0])?$params[0]:"default";
    
    switch($mode)
    {
        case "error":
        {
            $t=new TemplateProcessor("calender/createevent");
            $t->tokens['error']="Invalid input.";       
            EngineCore::AddPageContent($t->process(true));
            EngineCore::SetPageTitle("Create event");
            return;
        }
        case "date":
        {
            $t=new TemplateProcessor("calender/createevent");
            if(isset($params[1]))
            {
                $datestring= ModuleFunction_calender_ParseYYYYMMDD($params[1]);
                $t->tokens['date']=$datestring;      
            }
            EngineCore::AddPageContent($t->process(true));
            EngineCore::SetPageTitle("Create event");
            return;
        }
        default:
        {
            $t=new TemplateProcessor("calender/createevent");
            EngineCore::SetPageTitle("Create event");
            return;
        }
    }
}

function ModuleFunction_calender_ShowDay($day)
{
    list($y,$m,$d)= ModuleFunction_calender_ParseYYYYMMDD($day,true);
    $eventIds= CalendarScheduler::CheckDate($y,$m,$d);
    if(!$eventIds || count($eventIds)==0)
    {
        echo "fuck";
        return;
    }
    $events=[];
    $output="";
    foreach($eventIds as $event)
    {
        $e=new EVA($event);
        $events[]=$e;
        $t=new TemplateProcessor("calender/displayeventlist");
        $t->tokens['title']=$e->attributes['title'];
        $t->tokens['date']=$e->attributes['calendar.date'];
        $t->tokens['time']=$e->attributes['calendar.time'];
        $t->tokens['description']=$e->attributes['description'];
        $output.=$t->process(true);
    }
    $t = new TemplateProcessor("calender/eventsondate");
    $t->tokens['events']=$output;
    EngineCore::AddPageContent($t->process(true));
    EngineCore::SetPageTitle("Events on ".$e->attributes['calendar.date']);
}


function ModuleFunction_calender_ShowMonth($month,$doupcoming=false)
{
    list($y,$m,$d) = ModuleFunction_calender_ParseYYYYMMDD($month."01",true);
    $output="";
    
    $currentmonth = date_create_from_format("Ymd",$y.$m.$d);
    
    $now= new DateTime();
    $today = $now->format("j");
    $isthismonth = $now->format("Ym") == $month;
    
    
    
    // find out the first day of the week 
    $weekfirst=$currentmonth->format("w")== 0 ? 6 : $currentmonth->format("w")-1;
    
    // create objects for previous and next months for display
    $onemonthago=new DateInterval("P1M");
    
    $next_month = date_create_from_format("Ymd",$y.$m.$d);
    $next_month->add($onemonthago);
    
    $onemonthago->invert=true;
    $prev_month = date_create_from_format("Ymd",$y.$m.$d);
    $prev_month->add($onemonthago);
    
    //find out how many days of the previous month to show and which numbers
    $daysprevmont =$prev_month->format("t");
    
    $headerprev=$prev_month->format("M Y");
    $headercurrent=$currentmonth->format("M Y");
    $headernext=$next_month->format("M Y");
    $headerlinkprev=$prev_month->format("Ym");
    $headerlinknext=$next_month->format("Ym");
    
    // insert grayed out previous month's days to fill the week
    $t_prev=new TemplateProcessor("calender/daycellprev");
    for($i = 0; $i<$weekfirst;$i++)
    {
        $t_prev->tokens['number']=($daysprevmont-$weekfirst+$i+1);
        $output.=($t_prev->process(true));
    }
    
    //-----
    
    ///////////actual month
    $t_day = new TemplateProcessor("calender/daycell");
    $t_marker = new TemplateProcessor("calender/daycellmarker");
    
    // find out current month's day
    $daysthismont =$currentmonth->format("t");
    $events_upcoming=[];
    $events_today=[];
    for($i=0;$i<$daysthismont;$i++)
    {
        
        $actives = [];
        $dates = CalendarScheduler::CheckDate($y,$m,str_pad($i+1,2,"0",STR_PAD_LEFT));
        if($dates)
        {
            $actives[]="";
            $actives[]="adm";

            
        }
        if($isthismonth && $today == $i+1)
        {
            $t_today= new TemplateProcessor("calender/daycelltoday");
            $t_today->tokens['number']=$today;
            $output.=$t_today->process(true);
            foreach($dates as $date)
            {
                $events_today[]= new CalendarEvent($date);
            }
        }
        else
        {
            $divstring="";
            $thisdate=date_create_from_format("j m Y", ($i+1)." ".$currentmonth->format("m Y"));
            $datestring = $thisdate->format("Ymd");
            
            if($i+1>$today)
            {
                foreach($dates as $date)
                {
                    $events_upcoming[]= new CalendarEvent($date);
                }
            }
            
            foreach($actives as $line)
            {
                $t_marker->tokens['marker']=$line;
                $divstring.=$t_marker->process(true);
            }
            $t_day->tokens['date']=$datestring;
            $t_day->tokens['number']=($i+1);
            $t_day->tokens['markers']=$divstring;
            if($actives)
            {
                $t_day->tokens['verb']="view";
            }
            else
            {
                $t_day->tokens['verb']="create";
            }
            $output.=$t_day->process(true);
        }
        
    }
    
    /////////end month
    $next_month_start=($daysthismont-28)+($weekfirst);
    $next_month_start%=7;
    $fifthrowcount=7-$next_month_start;
    
    $fifthrowcount%=7;
    $t_next = new TemplateProcessor("calender/daycellnext");
    for($i=0;$i<$fifthrowcount;$i++)
    {
        $t_next->tokens['number']=$i+1;
        $output.=($t_next->process(true));
    }
    $t_header= new TemplateProcessor("calender/monthheader");
    $t_header->tokens =[
        "prevYYYYMM" => $headerlinkprev,
        "prev" => $headerprev,
        "current" => $headercurrent,
        "next" => $headernext,
        "nextYYYYMM" => $headerlinknext
    ];
    $t_month= new TemplateProcessor("calender/calendarmonth");
    $t_month->tokens['header']=$t_header->process(true);
    $t_month->tokens['days']=$output;
    EngineCore::SetPageContent($t_month->process(true));
    if($doupcoming)
    {
        $t_upcoming = new TemplateProcessor("calender/upcoming");
        $t_upcoming->tokens['upcoming']=$events_upcoming;
        $t_upcoming->tokens['today']=$events_today;
        
       
        EngineCore::AddPageContent($t_upcoming->process(true));
        
    }
}




function ModuleAction_calender_view($params)
{
    if(!isset($params[0]))
    {
        ModuleAction_calender_default($params);
    }
    switch ($params[0])
    {
        case "month":
        {
            ModuleFunction_calender_ShowMonth($params[1]);
            return;
        }
        case "date":
        {
            ModuleFunction_calender_ShowDay($params[1]);
            return;
        }
        default:
        {
            ModuleAction_calender_default($params);
        }
    }
}
