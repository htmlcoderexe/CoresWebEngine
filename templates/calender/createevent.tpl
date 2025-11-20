<h3>{%error|%}</h3>
<form action ="/calender/{%verb|create%}" method ="POST">
    <label for="title">Event title</label><br />
    <input name="title" id ="title" type ="text" value="{%title|%}"/>
    <br />
    <label for="date">Event date YYYY-MM-DD</label><br />
    <input name="date" id ="date" type ="date" value="{%year|1970%}-{%month|01%}-{%day|01%}" />
    <br />
    <label for="time">Event time HH:MM</label><br />
    <input name="time" id ="time" type ="time"  value="{%hour|00%}:{%minute|00%}"/>
    <br />
    <label for="timeD">Event duration</label><br />
    <input name="timeD" id ="timeD" type ="time" value="{%duration_hours|01%}:{%duration_minutes|00%}" />
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
<form action="/calender/fromevent/{%id%}" method="POST">
    {{calender/recurpickercontrol|calendar.recurring.data=7|calendar.recurring.type=day}}
    <button type="submit">Make recurring</button>
</form>
#}