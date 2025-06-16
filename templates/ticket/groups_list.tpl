<h3>Ticket groups</h3>
<ul>
{#foreach|{%groups%}|   <li>{:value:} <a href="/ticket/groups/{:object_id:}">Show tickets</a> &pipe; <a href="/ticket/groups/edit/{:object_id:}">Modify</a></li>
#}
</ul>