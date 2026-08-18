<meta http-equiv="refresh" content="300">
<h1 class="cal-week-title"><a href="/calender/view/week/{%prevyear%}/{%prevweek%}"> &larr;</a> <a href="/calender/view/month/{%year%}{%month%}">{%year|1691%}  W{%weekno|-1%}</a> <a href="/calender/view/week/{%nextyear%}/{%nextweek%}">&rarr;<!--hear me rawr lmao--> </a></h1>
<div class="cal-weekview">
    
      <br />
        <div class="cal-week-agenda-bg">    
          <div class="cal-week-header-wrapper">
              {#foreach|{%days%}|<span class="cal-week-header{:style:}">
                <a href="/calender/view/date/{:date:}">
                    <span class="cal-day-label">{:title:}</span>
                </a>
                </span>#}
          </div>
    {#ifset|marker|{%marker%}<span id="marker" class="cal-week-marker"  style="left: calc({:xpos:}% + 3em); top: calc({:ypos:}em + 2em)">&nbsp;</span><script type="text/javascript">document.getElementById("marker").scrollIntoView({ behavior: "smooth", block: "center" });</script>|#}
    </div>
</div>
<script>

function $(n)
{
    return document.createElement(n);
}

let events = {#json|{%events%}#};
let styles = {#json|{%styles%}#};
let week = {%week%};
let year = {%year%};
let agenda = document.querySelector('.cal-week-agenda-bg');

console.log(events);

let earliest = 7;
let latest = 21;

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