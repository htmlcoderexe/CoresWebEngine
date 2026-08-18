<meta http-equiv="refresh" content="300">
<h1 class="cal-week-title"><a href="/calender/view/week/{%prevyear%}/{%prevweek%}"> &larr;</a> <a href="/calender/view/month/{%year%}{%month%}">{%year|1691%}  W{%weekno|-1%}</a> <a href="/calender/view/week/{%nextyear%}/{%nextweek%}">&rarr;<!--hear me rawr lmao--> </a></h1>
<div class="cal-weekview">
    
      <br />
        <div class="cal-week-agenda-bg">    
        {#foreach|{#count|23|0|1|2#}|<span class="cal-week-timegrid" style="top:calc({:*:}em * 2 + 2em)">{:*:}:00</span>#} {#ifset|events|{#foreach|{%events%}|<div class="cal-week-event" style="left: calc({:xpos:}% + 3em); top: calc({:ypos:}em + 2em);width: {:width:}%;height:{:height:}em{#ifeq|{:colour:}|||;background-color: {:colour:}#}"><a href="/calender/view/date/{:date:}">{:title:}</a></div>
#}|#}  <div class="cal-week-header-wrapper">{#foreach|{%days%}|<span class="cal-week-header{:style:}"><a href="/calender/view/date/{:date:}"><span class="cal-day-label">{:title:}</span></a></span>#}</div>
    {#ifset|marker|{%marker%}<span id="marker" class="cal-week-marker"  style="left: calc({:xpos:}% + 3em); top: calc({:ypos:}em + 2em)">&nbsp;</span><script type="text/javascript">document.getElementById("marker").scrollIntoView({ behavior: "smooth", block: "center" });</script>|#}
    </div>
</div>
