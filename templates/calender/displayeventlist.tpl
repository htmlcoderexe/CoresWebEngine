<div class="cal-display-event">
    <h3>{%title|&lt;Untitled&gt;%}</h3>
    {#ifeq|{%recurring|%}|true||<h3>{%calendar.date|%}</h3>#}
    {#ifeq|{%recurring|%}|true|<h4>Recurring</h4>|#}
    {#ifeq|{%calendar.time|%}|||<h3>{%calendar.time|%}</h3>#}
    <span class="cal-description">{%description|%}</span><br />
    {#ifeq|{%recurring|%}|true|
<form action="/calender/except/{%recurId%}" method="POST">
    <input type="hidden" value="{%calendar.date%}" name="date" />
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
