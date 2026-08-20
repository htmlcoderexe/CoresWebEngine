<meta http-equiv="refresh" content="300">
<h1 class="cal-week-title"></h1>
<div class="cal-weekview">
    <div class="cal-week-agenda-bg"></div>
</div>
</div>
<script>

let events = {#json|{%events%}#};
let styles = {#json|{%styles%}#};
let week = {%week%};
let year = {%year%};
let agenda = document.querySelector('.cal-week-agenda-bg');

let topheader = $q('.cal-week-title');

console.log(events);

RenderWeekCalendar(week,year,events,styles,agenda,topheader);

    


</script>