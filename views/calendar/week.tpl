<meta http-equiv="refresh" content="300">
<h1 class="cal-week-title"></h1>
<div class="cal-weekview">
    
      <br />
        <div class="cal-week-agenda-bg">    
          </div>
    {#ifset|marker|{%marker%}<span id="marker" class="cal-week-marker"  style="left: calc({:xpos:}% + 3em); top: calc({:ypos:}em + 2em)">&nbsp;</span><script type="text/javascript">document.getElementById("marker").scrollIntoView({ behavior: "smooth", block: "center" });</script>|#}
    </div>
</div>
<script>

function $(n)
{
    return document.createElement(n);
}

function $id(id)
{
    return document.getElementById(id);
}

function $q(s)
{
    return document.querySelector(s);
}
function $qa(s)
{
    return document.querySelectorAll(s);
}

let events = {#json|{%events%}#};
let styles = {#json|{%styles%}#};
let week = {%week%};
let year = {%year%};
let agenda = document.querySelector('.cal-week-agenda-bg');

let topheader = $q('.cal-week-title');

console.log(events);



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

let earliest = 7;
let latest = 21;

for(let h = 0; h<24;h++)
{
    let s = $('span');
    s.classList.add('cal-week-timegrid');
    s.innerText=lp(h)+":00";
    s.style.top=(2+2*h)+"em";
    agenda.appendChild(s);
}



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
    
    let header = $('div');
    header.classList.add('cal-week-header-wrapper');
    
    
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


let a_pr = $('a');
a_pr.innerHTML = '&larr;';
let a_mn = $('a');
let a_nx = $('a');
a_nx.innerHTML = '&rarr;'; // hear me rawr lmao
/*
    let dateprevweek = new Date(Monday.valueOf());
    dateprevweek.setDate(dateprevweek.getDate()-7);
    let datenxweek = new Date(Monday.valueOf());
    datenxweek.setDate(datenxweek.getDate()+7);
//*/
    let p_y = week==1?year-1:year;
    let p_w = week==1?52:week-1;
    let n_y = week==52?year+1:year;
    let n_w = week==52?1:week+1;
    let ytext = Monday.getFullYear();
    //console.log(Monday, dateprevweek, datenxweek, p_y, p_w, n_y, n_w);
    //*
    if(Monday.getFullYear() < year)
    {
        ytext+="-"+(Monday.getFullYear()+1);
    }
    //*/
    a_pr.href = '/calendar/view/week/'+p_y+"/"+p_w;
    a_mn.href = '/calendar/view/month/'+Monday.getFullYear()+"/"+(Monday.getMonth()+1);
    a_mn.innerText = ytext+"W"+week;
    a_nx.href = '/calendar/view/week/'+n_y+"/"+n_w;
    topheader.appendChild(a_pr);
    topheader.appendChild(a_mn);
    topheader.appendChild(a_nx);

// first append the gridlines to agenda bg

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
// then append div.cal-week-header-wrapper

// then shit out day headers into .cal-week-header-wrapper

// then if this week, shit out a marker:

// <span id="marker" class="cal-week-marker"  style="left: calc({:xpos:}% + 3em); top: calc({:ypos:}em + 2em)">&nbsp;</span>

// 
</script>