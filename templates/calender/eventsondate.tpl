<div class="cal-list-events">
    {#ifset|datestring|<h3>{%datestring%}</h3>#}
    <a class="action_button" href="/calender/view/month/{%month%}">Go back</a>
    <a class="action_button" href="/calender/create/date/{%date%}">Add new</a>
    {%events|%}
</div>