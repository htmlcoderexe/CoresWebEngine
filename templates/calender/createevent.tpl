<h3>{%error|%}</h3>
<form action ="/calender/{%verb|create%}" method ="POST">
    <label for="title">Event title</label><br />
    <input name="title" id ="title" type ="text" value="{%title|%}"/>
    <br />
    <label for="date">Event date YYYY-MM-DD</label><br />
    <input name="date" id ="date" type ="date" value="{%date|%}" />
    <br />
    <label for="time">Event time HH:MM</label><br />
    <input name="time" id ="time" type ="time"  value="{%startTime|%}"/>
    <br />
    <label for="timeD">Event duration</label><br />
    <input name="timeD" id ="timeD" type ="time" value="{%duration|01:00%}" />
    <br />
    <label for="type">Event type</label><br />
    <select id="type" name="type">
        {#foreach|{%types%}|<option style="background-color:{:calendar.tagcolour:}" value="{:typeId:}" {#ifeq|{:typeId:}|{%type%}|selected="selected"#}>{:name:}</option><!--{%type|-1%}-->#}
    </select>
    <label for="description">Event description</label><br />
    <textarea name="description" id ="description">{%description|%}</textarea>
    <input name ="create" type="hidden" value ="true" /><br />
    <input name ="EventID" type ="hidden" value ="{%eventId|-1%}" />
    <button type="submit">Save</button>
</form>{#ifeq|{%eventId|-1%}|-1||
<form action="/calender/fromevent/{%eventId%}" method="POST">
    <label for="rtype">recur type</label><input id="rtype" name="rtype" value="week" />
    <label for="rdata">recur data</label><input id="rtype" name="rdata" value="*****.."/>
    <button type="submit">Make recurring</button>
</form>
#}