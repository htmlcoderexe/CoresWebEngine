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

function ModuleFunction_EditEvent($eID,$title,$date,$time,$duration,$description,$type)
{
    if(!$title || !$date||!$eID)
    {
        EngineCore::GTFO("/calender/edit/error");
        return;
    }
    $event = new EVA($eID);
    if(!$event)
    {
        EngineCore::GTFO("/calender/edit/error");
        return;
    }
    $event->SetSingleAttribute("title",$title);
    $event->SetSingleAttribute("calendar.date",$date);
    if(!$time)
    {
        $time ="00:00";
    }
    if(!$duration)
    {
        $duration="01:00";
    }
    $event->SetSingleAttribute("calendar.time",$time);
    $event->SetSingleAttribute("calendar.duration",$duration);
    $event->SetSingleAttribute("description",$description);
    $event->SetSingleAttribute("calendar.event_type",$type);
    $event->Save();
    EngineCore::GTFO("/calender");
    return;
}

function ModuleFunction_CreateEvent($title,$date,$time,$duration,$description,$type)
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
    if(!$duration)
    {
        $duration="01:00";
    }
    $event->AddAttribute("calendar.time",$time);
    $event->AddAttribute("calendar.duration",$duration);
    $event->AddAttribute("description",$description);
    $event->AddAttribute("calendar.event_type",$type);
    $event->Save();
    EngineCore::GTFO("/calender");
    return;
}

function ModuleFunction_calender_ManageTypes()
{
    $types=EVA::GetAllOfType("calendar.event.type");
    $tpl=new TemplateProcessor("calender/managetypes");
    foreach($types as $type)
    {
        $t=new EVA($type);
        $flattype=(array)($t->attributes);
        $flattype['id']=$t->id;
        $tpl->tokens['types'][]=$flattype;
    }
    EngineCore::AddPageContent($tpl->process(true));
}

function ModuleFunction_calender_CreateUpdate($tagname, $tagcolour,$schedulecolour,$id=-1)
{
    if($id==-1)
    {
        $e=EVA::CreateObject("calendar.event.type");
    }
    else
    {
        $e=new EVA($id);
    }
    $e->SetSingleAttribute("calendar.tagcolour",$tagcolour);
    $e->SetSingleAttribute("name",$tagname);
    $e->SetSingleAttribute("calendar.agendacolour",$schedulecolour);
    $e->Save();
    EngineCore::GTFO("/calender/type/manager/");
    die();
}

function ModuleAction_calender_type($params)
{
    $action=$params[0]??"manager";
    switch($action)
    {
        case "edit":
        case "create":
        {
            $id=$params[1]??-1;
            $tpl=new TemplateProcessor("calender/edittype");
            $item=new EVA($id);
            //var_dump($item);//die;
            if($item->id!=null && $id!=-1)
            {
                $tpl->tokens['name']=$item->attributes['name'];
                $tpl->tokens['agendacolour']=$item->attributes['calendar.tagcolour'];
                $tpl->tokens['tagcolour']=$item->attributes['calendar.agendacolour'];
                $tpl->tokens['typeId']=$item->id;
            }
            EngineCore::AddPageContent($tpl->process(true));
            return;
        }
        case "update":
        {
            $name=EngineCore::POST("name");
            $tc=EngineCore::POST("tagcolour");
            $ac=EngineCore::POST("agendacolour");
            $id=EngineCore::POST("TypeID");
            if($name=="")
            {
                $name="No name";
            }
            if(EngineCore::POST("create"))
            {
                ModuleFunction_calender_CreateUpdate($name, $tc, $ac,$id);
            }
        }
        case "manager":
        {
            ModuleFunction_calender_ManageTypes();
            return;
        }
        default:
        {
            ModuleFunction_calender_ManageTypes();
            return;            
        }
    }
}


function ModuleAction_calender_edit($params)
{
    $selectedId=$params[0] ?? -1;
    $title = EngineCore::POST("title","");
    $date = EngineCore::POST("date","");
    $time = EngineCore::POST("time","");
    $duration = EngineCore::POST("timeD","");
    $description = EngineCore::POST("description","");
    $submitted=EngineCore::POST("create","");
    $eventId=EngineCore::POST("EventID");
    $type=EngineCore::Post("type","");
    if($submitted && $eventId && (new EVA($eventId))!=null)
    {
        ModuleFunction_EditEvent($eventId,$title,$date,$time,$duration,$description,$type);
        return;
    }
    $mode = $params[1] ?? "default";
    
    if(new EVA($selectedId)==null)
    {
        EngineCore::GTFO("/calender");
        return;
    }
    
    switch($mode)
    {
        case "error":
        {
            $t=new TemplateProcessor("calender/createevent");
            $t->tokens['error']="Invalid input.";       
            
            $t->tokens['types']=[];
            $types=EVA::GetAllOfType("calendar.event.type");
            foreach($types as $type)
            {
                $e=new EVA($type);
                $flattype=(array)($e->attributes);
                $flattype['typeId']=$e->id;
                $t->tokens['types'][]=$flattype;
            }
            EngineCore::AddPageContent($t->process(true));
            EngineCore::SetPageTitle("Create event");
            return;
        }
        default:
        {
            $t=new TemplateProcessor("calender/createevent");
            $types=EVA::GetAllOfType("calendar.event.type");
            $event = new CalendarEvent($selectedId);
            $t->tokens=(array)$event;
            $t->tokens['verb']="edit";
            $t->tokens['eventId']=$event->EvaInstance->id;
            $t->tokens['type']=$event->type;
            $t->tokens['types']=[];
            foreach($types as $type)
            {
                $e=new EVA($type);
                $flattype=(array)($e->attributes);
                $flattype['typeId']=$e->id;
                $t->tokens['types'][]=$flattype;
            }
            EngineCore::AddPageContent($t->process(true));
            EngineCore::SetPageTitle("Editing event");
            return;
        }
    }
}


function ModuleAction_calender_create($params)
{
    $title = EngineCore::POST("title","");
    $date = EngineCore::POST("date","");
    $time = EngineCore::POST("time","");
    $duration = EngineCore::POST("timeD","");
    $description = EngineCore::POST("description","");
    $submitted=EngineCore::POST("create","");
    $type=EngineCore::Post("type","");
    if($submitted)
    {
        ModuleFunction_CreateEvent($title,$date,$time,$duration,$description,$type);
        return;
    }
    $mode = $params[0] ?? "default";
    
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
            $t->tokens['types']=[];
            $types=EVA::GetAllOfType("calendar.event.type");
            foreach($types as $type)
            {
                $e=new EVA($type);
                $flattype=(array)($e->attributes);
                $flattype['typeId']=$e->id;
                $t->tokens['types'][]=$flattype;
            }
            EngineCore::AddPageContent($t->process(true));
            EngineCore::SetPageTitle("Create event");
            return;
        }
        default:
        {
            $t=new TemplateProcessor("calender/createevent"); $t->tokens['types']=[];
            $types=EVA::GetAllOfType("calendar.event.type");
            foreach($types as $type)
            {
                $e=new EVA($type);
                $flattype=(array)($e->attributes);
                $flattype['typeId']=$e->id;
                $t->tokens['types'][]=$flattype;
            }
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
        //echo "fuck";
       // return;
    }
    $events=[];
    $output="";
    $t=new TemplateProcessor("calender/displayeventlist");
        
    foreach($eventIds as $event)
    {
        $e=new EVA($event);
        $events[]=$e;
        $t->tokens = $e->attributes;
        $t->tokens['eventId']=$e->id;
        $t->tokens['calendar.date']="";
        $output.=$t->process(true);
    }
    $datestring = substr($day,0,4)."-".substr($day,4,2)."-".substr($day,6,2);
    $recurs = RecurringEvent::CheckDate($datestring);
    foreach($recurs as  $recur)
    {
        $t->tokens = $recur;
        $t->tokens['eventId']=0;
        $t->tokens['recurring']="true";
        $output.=$t->process(true);
    }
    $t2 = new TemplateProcessor("calender/eventsondate");
    $t2->tokens['events']=$output;
    EngineCore::AddPageContent($t2->process(true));
    EngineCore::SetPageTitle("Events on ".$datestring);
}
function ModuleFunction_calender_ShowEvent($eventID)
{
        $e=new EVA($eventID);
        if(!$e)
        {
         return;   
        }
        
        $t=new TemplateProcessor("calender/displayeventlist");
        $t->tokens = $e->attributes;
        $t->tokens['eventId']=$e->id;
        $output=$t->process(true);
    
    $t = new TemplateProcessor("calender/eventsondate");
    $t->tokens['events']=$output;
    EngineCore::AddPageContent($t->process(true));
    EngineCore::SetPageTitle("Events on ".$e->attributes['calendar.date']);
}


function ModuleFunction_calender_ShowMonth($month,$doupcoming=false)
{
    EngineCore::StartLap();
    list($y,$m,$d) = ModuleFunction_calender_ParseYYYYMMDD($month."01",true);
    $output="";
    //RecurringEvent::DoMonth($y,$m);
    $recurrings= RecurringEvent::CheckMonth($y,$m);
    EngineCore::Lap2Debug("got recurrers");
    //$recurrings =[];
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
    $headercurrent=$currentmonth->format("F Y");
    $headernext=$next_month->format("M Y");
    $headerlinkprev=$prev_month->format("Ym");
    $headerlinknext=$next_month->format("Ym");
    EngineCore::Lap2Debug("prep work");
    // insert grayed out previous month's days to fill the week
    $t_prev=new TemplateProcessor("calender/daycellprev");
    for($i = 0; $i<$weekfirst;$i++)
    {
        $t_prev->tokens['number']=($daysprevmont-$weekfirst+$i+1);
        $output.=($t_prev->process(true));
    }
    
    //-----
    
    ///////////actual month
    EngineCore::Lap2Debug("done prev month");
    $t_day = new TemplateProcessor("calender/daycell");
    $t_marker = new TemplateProcessor("calender/daycellmarker");
    
    // find out current month's day
    $daysthismont =$currentmonth->format("t");
    $firstweek=$currentmonth->format("W");
    $lastday=date_create_from_format("Ymd",$y.$m.$daysthismont);
    $lastweek=$lastday->format("W");
    if(intval($lastweek)<intval($firstweek))
    {
        $lastweek+=52;
    }
    $events_upcoming=[];
    $events_today=[];
    $all_event_ids= CalendarScheduler::CheckMonth(intval($y),intval($m));
    $all_events = [];
    if($all_event_ids)
    {
        $all_events = EVA::GetAsTable(["calendar.date","calendar.time","title","description","calendar.event_type"], "calendar.event",$all_event_ids);
    }
    $m2=intval($m)+1;
    $y2=intval($y);
    if($m2>12)
    {
        $m2=1;
        $y2++;
    }
    EngineCore::Lap2Debug("got all events");
    $next_month_ids= CalendarScheduler::CheckMonth($y2,$m2);
    $next_month_events = [];
    if($next_month_ids)
    {
        $next_month_events = EVA::GetAsTable(["calendar.date","calendar.time","title","description","calendar.event_type"], "calendar.event",$next_month_ids);
    }
    EngineCore::Lap2Debug("got all prev events");
    //$events_today = [];
    $upcoming = [];
    $events_by_day = [];
    foreach($all_events as $id=>$event)
    {
       $c_d = (int) (explode("-",$event['calendar.date'])[2]);
       if(!isset($events_by_day[$c_d]))
       {
           $events_by_day[$c_d]=[];
       }
       $events_by_day[$c_d][]=$event;
       if($c_d == $today)
       {
           $events_today[]=$event;
       }
       elseif($c_d> $today)
       {
           $upcoming[]=$event;
       }
    }
    EngineCore::Lap2Debug("done processing month events");
    foreach($recurrings as $event)
    {
       $c_d = (int) (explode("-",$event['calendar.date'])[2]);
       if(!isset($events_by_day[$c_d]))
       {
           $events_by_day[$c_d]=[];
       }
       $events_by_day[$c_d][]=$event;
       if($c_d == $today)
       {
           $events_today[]=$event;
       }
       elseif($c_d> $today)
       {
           $upcoming[]=$event;
       }
    }
    EngineCore::Lap2Debug("done processing recurrings");
    foreach($next_month_events as $id=>$event)
    {
       
       $upcoming[]=$event;
    }
    EngineCore::Lap2Debug("filled upcoming");
    /*/
    EngineCore::Dump2Debug($events_by_day);
    EngineCore::Dump2Debug($all_event_ids);
    EngineCore::Dump2Debug($all_events);
    //*/
    
    $e_types=EVA::GetAllOfType("calendar.event.type");
    $mapping = EVA::GetAsTable(["calendar.tagcolour"],"calendar.event.type",$e_types);
    $default=array_keys($mapping)[0];
    
    EngineCore::Lap2Debug("got tag types");
    $marker_places = ["0px -8px", "0px 8px", "8px 0px", "-8px 0px"];
    
    for($i=0;$i<$daysthismont;$i++)
    {
        
        $actives = [];
        /*
        $dates = CalendarScheduler::CheckDate($y,$m,str_pad($i+1,2,"0",STR_PAD_LEFT));
        if($dates)
         * 
         */
        if(isset($events_by_day[$i+1]))
        {
            foreach($events_by_day[$i+1] as $event)
            {
                $etype=$event['calendar.event_type'] =="" ? $default : $event['calendar.event_type'];
                //$etype =$event['calendar.event_type'];
                $actives[]=$mapping[$etype]['calendar.tagcolour'];
            }

            
        }
        //*/
        if($isthismonth && $today == $i+1)
        {
            $t_today= new TemplateProcessor("calender/daycelltoday");
            $t_today->tokens['number']=$today;
            $output.=$t_today->process(true);
            /*/
            foreach($dates as $date)
            {
                $events_today[]= new CalendarEvent($date);
            }
            //*/
        }
        else
        {
            $divstring="";
            $thisdate=date_create_from_format("j m Y", ($i+1)." ".$currentmonth->format("m Y"));
            $datestring = $thisdate->format("Ymd");
            
            /*/
            if($i+1>$today)
            {
                foreach($dates as $date)
                {
                    $events_upcoming[]= new CalendarEvent($date);
                }
            }
            //*/
            $marker_count=0;
            foreach($actives as $marker_colour)
            {
                if($marker_count>3)
                {
                    break;
                }
                $t_marker->tokens['marker']=$marker_places[$marker_count];
                $t_marker->tokens['colour']=$marker_colour;
                $divstring.=$t_marker->process(true);
                $marker_count++;
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
    EngineCore::Lap2Debug("end month");
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
    $weeknos="";
    $t_week=new TemplateProcessor("calender/weekcell");
    for($i=$firstweek;$i<$lastweek+1;$i++)
    {
        $weekno=intval($i);
        $yearno=$y;
        if($weekno>52)
        {
            $weekno-=52;
            $yearno++;
        }
        $t_week->tokens['week']=$weekno;
        $t_week->tokens['year']=$yearno;
        $weeknos.=($t_week->process(true));
    }
    $t_month->tokens['weeks']=$weeknos;
    EngineCore::SetPageContent($t_month->process(true));
    if($doupcoming)
    {
        $t_upcoming = new TemplateProcessor("calender/upcoming");
        if(count($events_today) > 0)
        {
            $t_upcoming->tokens['events']=$events_today;
            EngineCore::AddSideBar("Today", $t_upcoming->process(true));
        }
        if(count($upcoming) > 0)
        {
            $t_upcoming->tokens['events']=$upcoming;
            EngineCore::AddSideBar("Upcoming", $t_upcoming->process(true));
        }
    }
}

function ModuleFunction_calender_TimeToEms($ems,$hh,$mm)
{
    return ((floatval($hh))*1.0*$ems +floatval($mm)/60.0*$ems);
}


function ModuleAction_calender_fromevent($params)
{
    $eid = $params[0];
    $evt = new CalendarEvent($eid);
    if(!$evt->isValid)
    {
        var_dump($evt);
        //EngineCore::GTFO("/calender");
        die;
    }
    $rtype = EngineCore::POST("rtype", RecurringEvent::RECUR_MONTH);
    $rdata = EngineCore::POST("rdata","1");
    $rec = RecurringEvent::Create($rtype,$rdata,$evt->title,$evt->description,$evt->startTime,$evt->duration,$evt->type);
    EngineCore::GTFO("/calender");
    die;
}

function ModuleFunction_calender_ShowWeek($year,$week)
{
    $now = time();
    $now+= EngineCore::GetTimeOffset();
    $nowhh=date("H",$now); 
    $nowmm=date("i",$now);
    $nowday=date("Ymd",$now);
    $tpl=new TemplateProcessor("calender/week");
    $events=[];
    $ems=2.0;
    $starting_hour=0.0;
    $marker=[];
    $daynames=[];//["November 18","November 19","November 20","November 21","November 22","November 23","November 24"];
    for($i =1;$i<8;$i++)
    {
        $date = strtotime($year."W".sprintf("%02u", $week).$i);
        $isred = $i==7;
        $isblue = $i==6;
        // TODO: check red days other than Sundays
        
        
        $ismonday=$i==1;
        $daystyle= ($ismonday?" cal-week-monday":"").($isred?" cal-week-redday":($isblue?" cal-week-blueday":""));
        $daynames[]=["date"=>date("Ymd",$date),"title"=>date("D j/m",$date),"style"=>$daystyle];
        $onthisday= CalendarScheduler::CheckDate(date("Y-m-d",$date));
        if($onthisday)
        {
            foreach($onthisday as $event)
            {
                
                $event_entry=(array)(new CalendarEvent($event));
                if(!$event_entry['isValid'])
                {
                    continue;
                }
                $etime =explode(":",$event_entry['startTime']);
                if(count($etime)==2)
                {
                    list($hh,$mm)=$etime;
                }
                else
                {
                    list($hh,$mm)=[0,0];
                }
                $edur=explode(":",$event_entry['duration']);
                if(count($edur)==2)
                {
                    list($dhh,$dmm)=$edur;
                }
                else
                {
                    list($dhh,$dmm)=[0,0];
                }
                $event_entry['xpos']=13*($i-1);
                $event_entry['ypos']= ModuleFunction_calender_TimeToEms($ems, floatval($hh)-$starting_hour, $mm);
                $event_entry['height']= ModuleFunction_calender_TimeToEms($ems,$dhh,$dmm);
                $event_entry['id']=$event;
                if($event_entry['type']!="")
                {
                    $etype=new EVA($event_entry['type']);
                    $col=$etype->attributes['calendar.agendacolour'];
                    $event_entry['colour']=$col;
                }
                $events[]=$event_entry;
            }
        }
        if($nowday==date("Ymd",$date))
        {
            $marker['xpos']=13*($i-1);
            $marker['ypos']= ModuleFunction_calender_TimeToEms($ems, $nowhh, $nowmm);
        }
        if($ismonday)
        {
            $month=date("m",$date);
        }
    }
    $tpl->tokens=[
        "year"=>$year,
        "weekno"=>$week,
        "month"=>$month,
        "prevyear"=>$week==1?$year-1:$year,
        "prevweek"=>$week==1?52:$week-1,
        "nextyear"=>$week==52?$year+1:$year,
        "nextweek"=>$week==52?1:$week+1
    ];
    $tpl->tokens['days']=$daynames;
    $tpl->tokens['events']=$events;
    if($marker)
    {
         $tpl->tokens['marker']=$marker;
    }
    EngineCore::SetPageContent($tpl->process(true));
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
        case "event":
        {
            ModuleFunction_calender_ShowEvent($params[1]);
            return;
        }
        case "week":
        {
            ModuleFunction_calender_ShowWeek($params[1],$params[2]);
            return;
        }
        default:
        {
            ModuleAction_calender_default($params);
        }
    }
}

function ModuleAction_calender_except($params)
{
    if(!isset($params[0]))
    {
        EngineCore::GTFO("/calender");
        die;
    }
    $id=intval($params[0]);
    $recurrer = RecurringEvent::Load($id);
    if(!$recurrer)
    {
        EngineCore::GTFO("/calender");
        die;
    }
    $action = EngineCore::POST("action","create");
    $date = EngineCore::POST("date","1970-01-01");
    $ymdstr = substr($date,0,4).substr($date,5,2).substr($date,8,2);
    $recurrer->AddException($date);
    if($action == "create")
    {
        EngineCore::GTFO("/calender/edit/".($recurrer->CreateOnDate($date))->id);
        die;
    }
    EngineCore::GTFO("/calender/view/date/".$ymdstr);
    die;
    
}

function ModuleAction_calender_delete($params)
{
    $id=EngineCore::POST("id_to_delete","-1");
    $e = new EVA($id);
    if(!$e)
    {
        EngineCore::GTFO("/calender");
        die;
    }
    // #TODO: some actual AAA ffs!!11
    
    EVA::DeleteObject($id);
    EngineCore::GTFO("/calender");
    die;
}