<h3>{%error|%}</h3>
<form action ="/calendar/recurring/save" method ="POST">
    {#CSRF#}
    <label for="title">Event title</label><br />
    <input name="title" id ="title" type ="text" value="{%title|%}"/>
    <br />
    <label for="date">Starting date YYYY-MM-DD</label><br />
    <input name="date" id ="date" type ="date" value="{#sprintf|%04d-%02d-%02d|{%year|1970%}|{%month|01%}|{%day|01%}#}" />
    <br />
    <label for="no_end_date">Repeat indefinitely</label>
    <input onchange="EnableDisableEndDate();" id="no_end_date"{#ifeq|{%end_date|0%}|0| checked |#} value="no" type="radio" name="end_date_option" />
    <br />
    <label for="yes_end_date">Repeat until:</label>
    <input onchange="EnableDisableEndDate();" id="yes_end_date"{#ifeq|{%end_date|0%}|0|| checked #} value="yes" type="radio" name="end_date_option" />
    <br />
    <input name="date_end" id ="date_end" type ="date" value="{#date|Y-m-d|{%end_date|0%}#}"{#ifeq|{%end_date|0%}|0| disabled |#} />
    <br />
    <label for="time">Event time HH:MM</label><br />
    <input name="time" id ="time" type ="time"  value="{#sprintf|%02d:%02d|{%hour|0%}|{%minute|0%}#}"/>
    <br />
    <label for="timeD">Event duration</label><br />
    <input name="timeD" id ="timeD" type ="time" value="{$duration|{%duration|0%}$}" />
    <br />
    <label for="type">Event type</label><br />
    <select id="type" name="type">
        {#foreach|{%types%}|<option style="background-color:{:marker_colour:}" value="{:id:}" {#ifeq|{:id:}|{%category%}|selected="selected"#}>{:name:}</option><!--{%category|-1%}-->#}
    </select>
    <br />
    <label for="description">Event description</label><br />
    <textarea name="description" id ="description">{%description|%}</textarea>
    <input name ="create" type="hidden" value ="true" /><br />
    <input name ="EventID" type ="hidden" value ="{%id|-1%}" />
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