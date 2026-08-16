<h3>Ticket groups</h3>
<a href="/tickets/groups/create">Create new group</a>
<ul>
{#foreach|{%groups%}|   <li>{:name:} &#x029EB; <a href="/tickets/list/{:gid:}">Show tickets (<strong>{:ticketcount:}</strong>)</a> &#x029EB; <a href="/tickets/groups/edit/{:gid:}">Modify</a></li>
#}
</ul>