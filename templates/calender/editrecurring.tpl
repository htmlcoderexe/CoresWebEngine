<h3>{%error|%}</h3>
<form action ="/calender/recurring/" method ="POST">
    <label for="title">Event title</label><br />
    <input name="title" id ="title" type ="text" value="{%title|%}"/>
    <br />
    <label for="date">Starting date YYYY-MM-DD</label><br />
    <input name="date" id ="date" type ="date" value="{%year|1970%}-{%month|01%}-{%day|01%}" />
    <br />
    <label for="no_end_date">Repeat indefinitely</label>
    <input onchange="EnableDisableEndDate();" id="no_end_date"{#ifeq|{%end_date|0%}|0| checked |#} value="no" type="radio" name="end_date_option" />
    <br />
    <label for="yes_end_date">Repeat until:</label>
    <input onchange="EnableDisableEndDate();" id="yes_end_date"{#ifeq|{%end_date|0%}|0|| checked #} value="yes" type="radio" name="end_date_option" />
    <br />
    <input name="date_end" id ="date_end" type ="date" value="{%end_date_yyyymmdd|%}"{#ifeq|{%end_date|0%}|0| disabled |#} />
    <br />
    <label for="time">Event time HH:MM</label><br />
    <input name="time" id ="time" type ="time"  value="{%hour|00%}:{%minute|00%}"/>
    <br />
    <label for="timeD">Event duration</label><br />
    <input name="timeD" id ="timeD" type ="time" value="{%duration_hours|01%}:{%duration_minutes|00%}" />
    <br />
    <label for="type">Event type</label><br />
    <select id="type" name="type">
        {#foreach|{%types%}|<option style="background-color:{:marker_colour:}" value="{:id:}" {#ifeq|{:id:}|{%category%}|selected="selected"#}>{:id:}</option><!--{%category|-1%}-->#}
    </select>
    <label for="description">Event description</label><br />
    <textarea name="description" id ="description">{%description|%}</textarea>
    <input name ="create" type="hidden" value ="true" /><br />
    <input name ="EventID" type ="hidden" value ="{%eventId|-1%}" />
    <h3>Recurrence options:</h3>
    {{calender/recurpickercontrol|calendar.recurring.data={%recur_data|7%}|calendar.recurring.type={%recur_type|day%}}}
    <button type="submit">Save</button>
</form>
<script>
    
function EnableDisableEndDate()
{
    document.getElementById("date_end").disabled = document.getElementById("no_end_date").checked;
}    
</script>