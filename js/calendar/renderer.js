        
    
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

function injectSidebarEvent(el, event)
{
    let title = document.createElement('h4');
    let when = document.createElement('span');
    let when_d = document.createElement('strong');
    let timestring = " ⌚All day";
    if(event.duration != 0)
    {
        let endtime = hhmmadd(event.hour, event.minute, 0, event.duration);
        timestring = " ⌚"+lp(event.hour) + ":" + lp(event.minute) + " - " + endtime.hh + ":" + endtime.mm;
    }
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
    b.href="/calender/view/week/"+y+"/"+w+"";
    a.appendChild(b);
    return a;
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
    
    