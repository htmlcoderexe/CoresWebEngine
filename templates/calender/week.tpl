<h1 class="cal-week-title"><a href="/calender/view/week/{%prevyear%}/{%prevweek%}"> &larr;</a> <a href="/calender/view/month/{%year%}{%month%}">{%year|1691%}  W{%weekno|-1%}</a> <a href="/calender/view/week/{%nextyear%}/{%nextweek%}">&rarr;<!--hear me rawr lmao--> </a></h1>
<div class="cal-weekview">
    
      <br />
        <div class="cal-week-agenda-bg">    
        {#foreach|{#count|23|0|1|2#}|<span class="cal-week-timegrid" style="top:calc({:*:}em * 2 + 2em)">{:*:}:00</span>#} {#ifset|events|{#foreach|{%events%}|<div class="cal-week-event" style="left: calc({:xpos:}% + 3em); top: calc({:ypos:}em + 2em);height:{:height:}em{#ifeq|{:colour:}|||;background-color: {:colour:}#}"><a href="/calender/view/event/{:id:}">{:title:}</a></div>
#}|#}  {#foreach|{%days%}|<span class="cal-week-header{:style:}"><a href="/calender/view/date/{:date:}">{:title:}</a></span>#}
    {#ifset|marker|{%marker%}<span class="cal-week-marker"  style="left: calc({:xpos:}% + 3em); top: calc({:ypos:}em + 2em)">&nbsp;</span>|#}
    </div>
</div>
