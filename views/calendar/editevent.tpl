<h3>{%error|%}</h3>
<form action ="/calendar/{%verb|create%}" method ="POST">
    {#CSRF#}
    <label for="title">Event title</label><br />
    <input name="title" id ="title" type ="text" value="{%title|%}"/>
    <br />
    <label for="date">Event date YYYY-MM-DD</label><br />
    <input name="date" id ="date" type ="date" value="{#sprintf|%04d-%02d-%02d|{%year|1970%}|{%month|01%}|{%day|01%}#}" />
    <br />
    <label for="time">Event time HH:MM</label><br />
    <input name="time" id ="time" type ="time"  value="{#sprintf|%02d:%02d|{%hour|0%}|{%minute|0%}#}"/>
    <br />
    <label for="timeD">Event duration</label><br />
    <input name="timeD" id ="timeD" type ="time" value="{$duration|{%duration|0%}$}" />
    <br />
    <label for="type">Event type</label><br />
    <select id="type" name="type">
        {#foreach|{%types%}|<option style="background-color:{:marker_colour:}" value="{:id:}" {#ifeq|{:id:}|{%type%}|selected="selected"#}>{:name:}</option><!--{%type|-1%}-->#}
    </select>
    <label for="description">Event description</label><br />
    <textarea name="description" id ="description">{%description|%}</textarea>
    <input name ="create" type="hidden" value ="true" /><br />
    <input name ="EventID" type ="hidden" value ="{%id|-1%}" />
    <button type="submit">Save</button>
</form>{#ifeq|{%id|-1%}|-1||
<form action="/calendar/recurring/from/{%id%}" method="POST">
    {#CSRF#}
    {{calender/recurpickercontrol|calendar.recurring.data=7|calendar.recurring.type=day}}
    <input type="hidden" value="{%id%}" name="eventId" />
    <button type="submit">Make recurring</button>
</form>
#}