<div class="cal-weekview">
    
      <br />
        <div class="cal-week-agenda-bg">    
        {#foreach|{#count|23|0|1|2#}|<span class="cal-week-timegrid" style="top:calc({:*:}em * 2 + 2em)">{:*:}:00</span>#} {#ifset|events|{#foreach|{%events%}|<div class="cal-week-event" style="left: calc({:xpos:}% + 2.5em); top: calc({:ypos:}em + 2em);height:{:height:}em">{:title:}</div>
#}|#}  {#foreach|{%daynames%}|<span class="cal-week-header">{:*:}</span>#}
    
    </div>
</div>
