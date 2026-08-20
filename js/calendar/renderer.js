        
    
/**
* Source: https://stackoverflow.com/a/79336072
* Thanks Jonas!
* Gets the ISO 8601 week number (zero-based) using local time.
* 
* @param {Date} date A `Date` object.
*/
function getISOWeek(date) {
    // get the week's Thursday (over/underflow in date parameter carries over)
    let thursday = new Date(
        date.getFullYear(),
        date.getMonth(),
        date.getDate() - (date.getDay() + 6) % 7 + 3 // Sunday is 0
    );
    /*
        Get January 1 of that Thursday's year. There is no need to
        find the first Thursday of the year since we round down the
        difference in weeks in the next step.
    */
    let janFirst = new Date(thursday.getFullYear(), 0, 1);
    /*
        The week number is the number of full weeks between `thursday`
        and `janFirst`. Both dates were created in local time, so to
        circumvent DST problems, use equivalent UTC dates (same year,
        month & date but at 00:00:00 in UTC).
    */
    return Math.floor(
        (
            thursday.getTime() - thursday.getTimezoneOffset() * 60000
            - janFirst.getTime() + janFirst.getTimezoneOffset() * 60000
        ) / 604800000 // milliseconds per week
    )+1;
}

function lp(num,length=2,chr = "0")
{
    let strnum = ""+num;
    let diff = length - strnum.length;
    if(diff>0)
    {
        return chr.repeat(diff)+strnum;
    }
    return strnum;
}

function hhmmadd(hh1,mm1,hh2,mm2)
{
    hh1 = Number(hh1);
    hh2 = Number(hh2);
    mm1 = Number(mm1);
    mm2 = Number(mm2);
    let mm = (mm1+mm2) % 60;
    if(mm<10)
        mm = "0"+mm;
    let hh = (hh1+hh2 + Math.floor((mm1+mm2)/60)) % 24;
    if(hh<10)
        hh = "0"+hh;
    return {hh, mm};
}

function GetTimeString(event)
{
    let timestring = " ⌚All day";
    if(event.duration != 0)
    {
        let endtime = hhmmadd(event.hour, event.minute, 0, event.duration);
        timestring = " ⌚"+lp(event.hour) + ":" + lp(event.minute) + " - " + endtime.hh + ":" + endtime.mm;
    }
    return timestring;
}

function injectSidebarEvent(el, event)
{
    let title = document.createElement('h4');
    let when = document.createElement('span');
    let when_d = document.createElement('strong');
    let timestring = GetTimeString(event);
    let when_t = document.createTextNode(timestring);
    when_d.innerText = event.day + "." + event.month;
    when.appendChild(when_d);
    when.appendChild(when_t);
    let desc = document.createElement('p');
    desc.innerText = event.description;
    let br = document.createElement('br');
    let hr = document.createElement('hr');
    el.appendChild(title);
    el.appendChild(when);
    el.appendChild(br);
    el.appendChild(desc);
    el.appendChild(hr);
}

function emitPrevCell(d)
{
    let a = document.createElement('span');
    let b = document.createElement('span');
    a.classList.add('cal-cell');
    b.classList.add('cal-next');
    b.classList.add('center_because_css_sucks');
    b.innerText = d;
    a.appendChild(b);
    return a;
}
function emitCurrentCell(y, m, d)
{
    let a = document.createElement('span');
    let b = document.createElement('span');
    let l = document.createElement('a');
    l.href = "/calendar/view/date/"+y+"/"+m+"/"+d;
    a.classList.add('cal-cell');
    b.classList.add('cal-cur');
    b.classList.add('center_because_css_sucks');
    b.innerText = d;
    a.appendChild(b);
    a.appendChild(l);
    return a;
}
function emitTodayCell(d)
{
    let a = document.createElement('span');
    let b0 = document.createElement('span');
    let b = document.createElement('span');
    a.classList.add('cal-cell');
    b0.classList.add('cal-today');
    b.classList.add('cal-cur');
    b.classList.add('center_because_css_sucks');
    b.innerText = d;
    a.appendChild(b0);
    a.appendChild(b);
    return a;
}
function emitWeekCell(n,y,w)
{
    let a = document.createElement('span');
    let b = document.createElement('a');
    a.classList.add('cal-week-cell');
    b.classList.add('cal-next');
    b.classList.add('center_because_css_sucks');
    b.innerText = n;
    b.href="/calendar/view/week/"+y+"/"+w+"";
    a.appendChild(b);
    return a;
}


/** 
 * Get the date from an ISO 8601 week and year
 *
 * stolen from here
 * https://stackoverflow.com/a/16591175
 * thanks Elle!
 *
 *
 * https://en.wikipedia.org/wiki/ISO_week_date
 *
 * @param {number} week ISO 8601 week number
 * @param {number} year ISO year
 *
 * Examples:
 *  getDateOfIsoWeek(53, 1976) -> Mon Dec 27 1976
 *  getDateOfIsoWeek( 1, 1978) -> Mon Jan 02 1978
 *  getDateOfIsoWeek( 1, 1980) -> Mon Dec 31 1979
 *  getDateOfIsoWeek(53, 2020) -> Mon Dec 28 2020
 *  getDateOfIsoWeek( 1, 2021) -> Mon Jan 04 2021
 *  getDateOfIsoWeek( 0, 2023) -> Invalid (no week 0)
 *  getDateOfIsoWeek(53, 2023) -> Invalid (no week 53 in 2023)
 */
function getDateOfIsoWeek(week, year) {
    week = parseFloat(week);
    year = parseFloat(year);
  
    if (week < 1 || week > 53) {
      throw new RangeError("ISO 8601 weeks are numbered from 1 to 53");
    } else if (!Number.isInteger(week)) {
      throw new TypeError("Week must be an integer");
    } else if (!Number.isInteger(year)) {
      throw new TypeError("Year must be an integer");
    }
  
    const simple = new Date(year, 0, 1 + (week - 1) * 7);
    const dayOfWeek = simple.getDay();
    const isoWeekStart = simple;

    // Get the Monday past, and add a week if the day was
    // Friday, Saturday or Sunday.
  
    isoWeekStart.setDate(simple.getDate() - dayOfWeek + 1);
    if (dayOfWeek > 4) {
        isoWeekStart.setDate(isoWeekStart.getDate() + 7);
    }

    // The latest possible ISO week starts on December 28 of the current year.
    if (isoWeekStart.getFullYear() > year ||
        (isoWeekStart.getFullYear() == year &&
         isoWeekStart.getMonth() == 11 &&
         isoWeekStart.getDate() > 28)) {
        throw new RangeError(`${year} has no ISO week ${week}`);
    }
  
    return isoWeekStart;
}


function timeSort(a, b) {
    let w =['year', 'month', 'day', 'hour','minute', 'second'];
    for(let i=0;i<w.length;i++)
    {
        if(a[w[i]]===b[w[i]])
            continue;
        return a[w[i]]-b[w[i]];
    }
    return 0;
}

function checkOverlap(a, b)
{
    return  (a['hour']*60+a['minute']) < (b['hour']*60+b['minute']+b['duration']) &&
            (a['hour']*60+a['minute']+a['duration']) > (b['hour']*60+b['minute']);
}

    
function RenderMonthCalendar(events, future_events, markers, y, m, container, headercontainer)
{

    let weeks = container.querySelector('.cal-weeks');
    
    let cal = container.querySelector('.cal-days');
    
    
    
    let last_month_days = new Date(y, m-1, 0).getDate();
    let today = new Date(y, m-1, 1);
    let first_day_of_week = today.getDay()-1;
    if(first_day_of_week == -1)
    {
        first_day_of_week = 6;
    }
    // dummy days of prev month
    let cellcount = 0;
    for(let i = last_month_days-first_day_of_week+1;i<=last_month_days;i++)
    {
        cellcount++;
        cal.appendChild(emitPrevCell(i));
    }
    let this_month_days = new Date(y, m, 0).getDate();
    // current month
    let realtoday = new Date();
    let thisyear = realtoday.getFullYear();
    let thismonth = realtoday.getMonth();
    let thisday = realtoday.getDate();
    
    let currentmonth = (thisyear==y && thismonth == m-1);
    
    let ontoday = [];
    let upcoming = [];
    
    
    for(let i = 0;i<this_month_days;i++)
    {
        cellcount++;
        let onthisday = events[i+1];
        let cell = emitCurrentCell(y,m,i+1);
        let bg ="";
        let fg ="";
        let slots = ["0px -8px", "0px 8px", "8px 0px", "-8px 0px"];
        if(onthisday)
        {
            let slot = 0;
            onthisday.forEach((e)=>{
                
                if(currentmonth && (i+1)==thisday)
                {
                    ontoday.push(e);
                }
                else 
                {
                    let styles = markers[e.category];
                    if(!styles)
                        return;
                    if(styles.number_colour!="#000000")
                        fg = styles.number_colour;
                    if(styles.bg_colour!="#000000")
                        bg = styles.number_colour;
                    if(styles.marker_colour!="#000000")
                    {
                        if(!currentmonth || ((i+1)>thisday))
                        {
                                upcoming.push(e);
                        }
                        if(slot<4)
                        {
                            let marker = document.createElement('span');
                            marker.innerHTML="&nbsp;";
                            marker.style.boxShadow = "inset "+slots[slot]+" 0px 0px "+styles.marker_colour;
                            cell.appendChild(marker);
                            slot++;
                        }
                    }
                }
            });
        }
        if(fg)
        {
            cell.style.color = fg;
        }
        if(bg)
        {
            cell.style.backgroundColor = bg;
        }
        if(currentmonth && (i+1)==thisday)
        {
            cell = emitTodayCell(i+1);
        }    
        cal.appendChild(cell);
    }
    // dummy days of next month
    let last_day_of_week = (first_day_of_week + this_month_days-1) % 7;
    if(last_day_of_week!=6)
    {
        for(let i = 1; i<(7-last_day_of_week);i++)
        {
            cellcount++;
            cal.appendChild(emitPrevCell(i));
        }
    }
    let numweeks = Math.round(cellcount/7);
    // weeks
    let firstweek = getISOWeek(today);
    let cellyear = y;
    for(let i = 0; i<numweeks;i++)
    {
        weeks.appendChild(emitWeekCell(firstweek,cellyear,firstweek));
        firstweek++;
        if(firstweek>52)
        {
            firstweek = 1;
            cellyear++;
        }
    }
    
    if(currentmonth)
    {
        if(ontoday.length>0)
        {
            let todaybox = AddToSideBar([], "Today").querySelector('.boxbody');
            ontoday.forEach((e)=>{injectSidebarEvent(todaybox,e);});
        }
        let box = AddToSideBar([], "Upcoming").querySelector('.boxbody');
        upcoming.forEach((e)=>{injectSidebarEvent(box,e);});
    }
    else
    {
        let box = AddToSideBar([], "Events").querySelector('.boxbody');
        upcoming.forEach((e)=>{injectSidebarEvent(box,e);});
    }
    
    let prev = document.createElement('a');
    let cur = document.createElement('span');
    let next = document.createElement('a');
    let opts = {month: 'long',year: 'numeric'};
    let fmt = new Intl.DateTimeFormat(undefined, opts);
    
    let py = m == 1 ? y-1 : y;
    let pm = m == 1 ? 11 : m-2;
    let ny = m == 12 ? y+1 : y;
    let nm = m == 12 ? 0 : m;
    let pdate = new Date(py, pm, 1);
    let cdate = new Date(y, m-1, 1);
    let ndate = new Date(ny, nm, 1);
    prev.innerText = fmt.format(pdate);
    prev.href="/calendar/view/month/"+py+"/"+(pm+1);
    cur.innerText = fmt.format(cdate);
    next.innerText = fmt.format(ndate);
    next.href="/calendar/view/month/"+ny+"/"+(nm+1);
    headercontainer.appendChild(prev);
    headercontainer.appendChild(cur);
    headercontainer.appendChild(next);
}


function RenderWeekCalendar(week,year,events,styles,agenda,topheader)
{
    

    // first append the gridlines to agenda bg

    for(let h = 0; h<24;h++)
    {
        let s = $('span');
        s.classList.add('cal-week-timegrid');
        s.innerText=lp(h)+":00";
        s.style.top=(2+2*h)+"em";
        agenda.appendChild(s);
    }


    // then shit out events with
    /*
     *  div class="cal-week-event" 
     *  style 
     *  left = :xpos:% + 3em); 
     *  top = :ypos: em + 2em);
     *  width = :width:%; height = :height: em 
     *  if set :colour:, background-color = :colour:
     *      a href="/calender/view/date/:date:" :title: /a
        /div

     * 
     */


    for(let wd = 1; wd <8; wd++)
    {
        let sortedDay = events[wd]?.sort(timeSort);
        console.log(sortedDay);
        if(sortedDay.length<1)
        {
            continue;
        }
        let filteredDay = [];
        sortedDay.forEach((d)=>{
            let style = styles[d.category] ?? {agenda_colour: '#7f7f7f'};
            d.colour =  style.agenda_colour;
            if(d.colour!='#000000')
            {
                filteredDay.push(d);
            }
            d.lane = 0;
            d.laneCount = 1;
        });
        console.log(filteredDay);

        for(let i = 0; i<filteredDay.length;i++)
        {
            let overlaps = [];
            for(let j = 0; j<i; j++)
            {
                if(checkOverlap(filteredDay[i], filteredDay[j]))
                {
                    overlaps.push(j);
                }
            }
            if(overlaps.length>0)
            {
                let lc = filteredDay[overlaps[0]].laneCount;
                if(overlaps.length == lc)
                {
                    overlaps.forEach((o)=>{
                        filteredDay[o].laneCount++;
                    });

                    filteredDay[i].laneCount = lc+1;
                    filteredDay[i].lane = lc;
                }
                else
                {
                    let newlane = 0;
                    for(let l = 0; l<lc;l++)
                    {
                        let taken = false;
                        overlaps.forEach((o)=>{
                            if(filteredDay[o].lane == l)
                                taken = true;
                        });
                        if(!taken)
                        {
                            newlane = l;
                            break;
                        }
                    }
                    filteredDay[i].lane = newlane;
                    filteredDay[i].laneCount = lc;
                }
            }

        }
        console.log(filteredDay);
        filteredDay.forEach((dd)=>{
            let div = $('div');
            div.classList.add('cal-week-event');
            let width = (13/dd.laneCount);
            let height = (dd.duration/30);
            div.style.width = width+"%";
            div.style.height = height+"em";
            let xpos = (wd-1)*13+(dd.lane*13/dd.laneCount);
            let ypos = dd.hour*2+2;
            div.style.left = 'calc('+xpos+'% + 3em)';
            div.style.top = ypos+"em";
            div.innerText = (dd.recur_data?"*":"")+dd.title;
            agenda.appendChild(div);
        });
    }



    // then append div.cal-week-header-wrapper

    let header = $('div');
    header.classList.add('cal-week-header-wrapper');

    // then shit out day headers into .cal-week-header-wrapper


    let opts = {weekday: 'long',day: 'numeric'};
    let fmt = new Intl.DateTimeFormat(undefined, opts);

    let Monday = getDateOfIsoWeek(week, year);

    for(let wd = 0; wd<7;wd++)
    {
        let s = $('span');
        s.classList.add("cal-week-header");
        if(wd == 0)
            s.classList.add("cal-week-monday");
        if(wd==6)
            s.classList.add("cal-week-redday");
        let date = new Date(Monday.valueOf());
        date.setDate(date.getDate()+wd);
        let a = $('a');
        a.href="/calendar/view/date/"+date.getFullYear()+"/"+(date.getMonth()+1)+"/"+date.getDate();
        let st = $('span');
        st.classList.add('cal-day-label');
        st.innerText = fmt.format(date);
        s.appendChild(a);
        a.appendChild(st);
        header.appendChild(s);
    }
    agenda.appendChild(header);

    // then if this week, shit out a marker:

    // <span id="marker" class="cal-week-marker"  style="left: calc({:xpos:}% + 3em); top: calc({:ypos:}em + 2em)">&nbsp;</span>

    let now = new Date();

    if(getISOWeek(now) == week)
    {
        let wd = now.getDay()-1;
        if(wd==-1)
            wd = 6;
        let marker = $('span');
        marker.classList.add('cal-week-marker');
        marker.id='cal-week-marker';
        agenda.appendChild(marker);
        marker.style.left = "calc("+(wd*13)+"% + 3em";
        marker.style.top = ((now.getHours()*60+now.getMinutes())/30+2)+"em";
        marker.scrollIntoView({ behavior: "smooth", block: "center" });
    }
    // then add the top header


    let a_pr = $('a');
    a_pr.innerHTML = '&larr;';
    let a_mn = $('a');
    let a_nx = $('a');
    a_nx.innerHTML = '&rarr;'; // hear me rawr lmao

    // wraparounds

    let p_y = week==1?year-1:year;
    let p_w = week==1?52:week-1;
    let n_y = week==52?year+1:year;
    let n_w = week==52?1:week+1;
    let ytext = Monday.getFullYear();
    // display weeks straddling the year with both years ok
    if(Monday.getFullYear() < year)
    {
        ytext+="-"+(Monday.getFullYear()+1);
    }
    a_pr.href = '/calendar/view/week/'+p_y+"/"+p_w;
    a_mn.href = '/calendar/view/month/'+Monday.getFullYear()+"/"+(Monday.getMonth()+1);
    a_mn.innerText = ytext+"W"+week;
    a_nx.href = '/calendar/view/week/'+n_y+"/"+n_w;
    topheader.appendChild(a_pr);
    topheader.appendChild(a_mn);
    topheader.appendChild(a_nx);

}
    
    
function RenderEvent(event, container, tpl, showdate = true)
{
    let date = new Date(event.year, event.month-1, event.day);
    let opts = {day: 'numeric', month: 'long',year: 'numeric'};
    let fmt = new Intl.DateTimeFormat(undefined, opts);
    
    
    $q('.cal-display-event-title',tpl).innerText = event.title ?? "<Untitled>";
    
    if(showdate)
    {
        $q('.cal-display-event-date',tpl).innerText = fmt.format(date);
    }
    
    if(!event.recurring)
    {
        $q('.cal-display-event-is-recurring',tpl).style.display = 'none';
    }
    
    $q('.cal-description',tpl).innerText = event.description ?? "(no description)";
    
    $q('.cal-display-event-duration',tpl).innerText = GetTimeString(event);
    
    if(event.recurId)
    {
        $q('.cal-display-event-exceptionForm',tpl).action="/calendar/except/"+event.recurId;
        $q('.cal-display-event-recurEdit',tpl).href="/calendar/recurring/"+event.recurId;
        $q("input[name='date']",tpl).value=event.year+"-"+event.month+"-"+event.day;
        
        $q('.cal-display-event-edit',tpl).style.display = 'none';
        $q('.cal-display-event-delete',tpl).style.display = 'none';
    }
    else
    {
        $q('.cal-display-event-exceptionForm',tpl).style.display = 'none';
        $q('.cal-display-event-recurEdit',tpl).style.display = 'none';
        
        $q('.cal-display-event-edit',tpl).href="/calendar/edit/"+event.id;
        $q("input[name='id_to_delete']",tpl).value = event.id;
    }
    container.appendChild(tpl);
}