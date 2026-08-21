<template id="event_tpl">
<div class="cal-display-event">
    <h3 class="cal-display-event-title"></h3>
    <h3 class="cal-display-event-date"></h3>
    <h4 class="cal-display-event-is-recurring">Recurring</h4>
    <span class="cal-description"></span><br />
    <h4 class="cal-display-event-duration"></h4>
<form action="" method="POST" class="cal-display-event-exceptionForm">
    {#CSRF#}
    <input type="hidden" value="" name="date" />
    <button type="submit" value="delete" name="action">Cancel today</button>
    <button type="submit" value="create" name="action">Edit today</button>
</form>
<a class="action_button cal-display-event-recurEdit" href="/calendar/recurring/">Edit</a>

<form action="/calendar/delete" method="POST" class="cal-display-event-delete">
    {#CSRF#}
    <input type="hidden" value="" name="id_to_delete" />
    <button type="submit">Delete</button>
</form>
<a class="action_button cal-display-event-edit" href="/calendar/edit/">Edit</a>
</div>
</template>
<div id="eventcontainer">
    
</div>
<script>
let event = {#json|{%event%}#};
let container = $id('eventcontainer');
let tpl = $id('event_tpl').content.cloneNode(true);
RenderEvent(event, container, tpl, true);
</script>