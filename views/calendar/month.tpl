        <meta http-equiv="refresh" content="300">
            <div class="cal-header">{%header|%}</div>
            {{system/clear}}
        <div class="cal-container" id="calendar">
            <div class="cal-days">
               
{%days|%}
            
</div>
<div class="cal-weeks">{%weeks%}</div>
        </div>
        {{system/clear}}
        
<script>
    
    let events = {#json|{%events%}#};
    console.log(events);
    let upcoming = {#json|{%next_month%}#}
    let markers = {#json|{%markers%}#}
    let y = {%year%};
    let m = {%month%};
    let container = document.getElementById('calendar');
    let header = document.querySelector('.cal-header');

    RenderMonthCalendar(events, upcoming, markers, y, m, container, header);
</script>