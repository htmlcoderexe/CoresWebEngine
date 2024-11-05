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
    $output="";
    $now= new DateTime();
    $today = $now->format("j");
    $weeknow= $now->format("w");
    $weeknow = $weeknow === 0 ? 6 : $weeknow -1;
    
    $header=$now->format("M Y");
    $y=$now->format("Y");
    $m=$now->format("n");
    
    
    $nowfirst= date_create_from_format("j m Y", "1 ".$now->format("m Y"));
    $weekfirst=$nowfirst->format("w");
    $weekfirst = $weekfirst === 0 ? 6 : $weekfirst-1;
    $weekfirst=intval($weekfirst);
    $daysthismont =$now->format("t");
    $onemonthago=new DateInterval("P1M");
    $onemonthago->invert=true;
    $now->add($onemonthago);
    $daysprevmont =$now->format("t");
    
    $t_prev=new TemplateProcessor("calender/daycellprev");
    for($i = 0; $i<$weekfirst;$i++)
    {
        $t_prev->tokens['number']=($daysprevmont-$weekfirst+$i+1);
        $output.=($t_prev->process(true));
    }
    
    ///////////actual month
    $now= new DateTime();
    $t_day = new TemplateProcessor("calender/daycell");
    $t_marker = new TemplateProcessor("calender/daycellmarker");
    for($i=0;$i<$today-1;$i++)
    {
        $actives = [];
        $divstring="";
        $thisdate=date_create_from_format("j m Y", ($i+1)." ".$now->format("m Y"));
        $datestring = $thisdate->format("Ymd");
        $dates = CalendarScheduler::CheckDate($y,$m,str_pad($i+1,2,"0",STR_PAD_LEFT));
        if($dates)
        {
            $actives[]="";
            $actives[]="adm";
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
        $output.=$t_day->process(true);
        
    }
    
    //today
    
    $t_today= new TemplateProcessor("calender/daycelltoday");
    $t_today->tokens['number']=$today;
    $output.=$t_today->process(true);
    
    //rest
    
    for($i=$today;$i<$daysthismont;$i++)
    {
        $actives = [];
        $divstring="";
        $thisdate=date_create_from_format("j m Y", ($i+1)." ".$now->format("m Y"));
        $datestring = $thisdate->format("Ymd");
        $dates = CalendarScheduler::CheckDate($y,$m,str_pad($i+1,2,"0",STR_PAD_LEFT));
        if($dates)
        {
            $actives[]="";
            $actives[]="adm";
        }
        foreach($actives as $line)
        {
            $t_marker->tokens['marker']=$line;
            $divstring.=$t_marker->process(true);
        }
        $t_day->tokens['date']=$datestring;
        $t_day->tokens['number']=($i+1);
        $t_day->tokens['markers']=$divstring;
        if(count($actives)>0)
        {
            $t_day->tokens['verb']="view";
        }
        else
        {
            $t_day->tokens['verb']="create";
        }
        $output.=$t_day->process(true);}
    
    
    /////////end month
    $fifthrowcount=7-($daysthismont-28)+($weekfirst)-1;
    
    $fifthrowcount%=7;
    $t_next = new TemplateProcessor("calender/daycellnext");
    for($i=0;$i<$fifthrowcount;$i++)
    {
        $t_next->tokens['number']=$i;
        $output.=($t_next->process(true));
    }
    
    $t_month= new TemplateProcessor("calender/calendarmonth");
    $t_month->tokens['header']=$header;
    $t_month->tokens['days']=$output;
    Utility::SetPageContent($t_month->process(true));
}

function ModuleFunction_CreateEvent($title,$date,$time,$description)
{
    if(!$title || !$date)
    {
        Utility::GTFO("/calender/create/error");
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
    Utility::GTFO("/calender");
    return;
}

function ModuleAction_calender_create($params)
{
    $title = Utility::POST("title","");
    $date = Utility::POST("date","");
    $time = Utility::POST("time","");
    $description = Utility::POST("description","");
    $submitted=Utility::POST("create","");
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
            Utility::AddPageContent($t->process(true));
            Utility::SetPageTitle("Create event");
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
            Utility::AddPageContent($t->process(true));
            Utility::SetPageTitle("Create event");
            return;
        }
        default:
        {
            $t=new TemplateProcessor("calender/createevent");
            Utility::SetPageTitle("Create event");
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
    Utility::AddPageContent($t->process(true));
    Utility::SetPageTitle("Events on ".$e->attributes['calendar.date']);
}

function ModuleAction_calender_view($params)
{
    if(!isset($params[0]))
    {
        ModuleAction_calender_default($params);
    }
    switch ($params[0])
    {
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
