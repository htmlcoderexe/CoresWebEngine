<template id="event_tpl">
<div class="cal-display-event">
    <h3 class="cal-display-event-title"></h3>
    <h3 class="cal-display-event-date"></h3>
    <h4 class="cal-display-event-is-recurring">Recurring</h4>
    <span class="cal-description"></span><br />
    <h4 class="cal-display-event-duration"></h4>
<form action="" method="POST" class="cal-display-event-exceptionForm">
    <input type="hidden" value="" name="date" />
    <button type="submit" value="delete" name="action">Cancel today</button>
    <button type="submit" value="create" name="action">Edit today</button>
</form>
<a class="action_button cal-display-event-recurEdit" href="/calender/recurring/">Edit</a>

<form action="/calender/delete" method="POST" class="cal-display-event-delete">
    <input type="hidden" value="" name="id_to_delete" />
    <button type="submit">Delete</button>
</form>
<a class="action_button cal-display-event-edit" href="/calender/edit/">Edit</a>
</div>
</template>
<div class="cal-list-events">
    
    
</div>
<script>
let events = {#json|{%events%}#};
let container =  $q('.cal-list-events');
events = events.sort(timeSort);
events.forEach((e)=>{
let tpl = $id('event_tpl').content.cloneNode(true);
    RenderEvent(e, container,tpl,false);
});
    
</script>
