<div class="cal-display-event">
    <h3>{%title|&lt;Untitled&gt;%}</h3>
    {#ifeq|{%nodate|%}|true||<h3>{%year|1970%}-{%month|01%}-{%day|01%}</h3>#}
    {#ifeq|{%recurring|%}|true|<h4>Recurring</h4>|#}
    {#ifeq|{%calendar.time|%}|||<h3>{%calendar.time|%}</h3>#}
    <span class="cal-description">{%description|(no description)%}</span><br />
    <h4>{#ifeq|{%duration|0%}|0|All day|{%hour|00%}:{%minute|00%} - {%done_hours|00%}:{%done_minutes|00%}#}
    {#ifeq|{%recurring|%}|true|
<form action="/calender/except/{%recurId%}" method="POST">
    <input type="hidden" value="{%year|1970%}-{%month|01%}-{%day|01%}" name="date" />
    <button type="submit" value="delete" name="action">Cancel today</button>
    <button type="submit" value="create" name="action">Edit today</button>
</form>
<a class="action_button" href="/calender/recurring/{%recurId%}">Edit</a>
|
<form action="/calender/delete" method="POST">
    <input type="hidden" value="{%eventId%}" name="id_to_delete" />
    <button type="submit">Delete</button>
</form>
<a class="action_button" href="/calender/edit/{%eventId%}">Edit</a>#}
</div>
