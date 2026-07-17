<h3>Ticket groups</h3>
<a href="/ticket/groups/create">Create new group</a>
<ul>
{#foreach|{%groups%}|   <li>{:name:} &#x029EB; <a href="/ticket/list/{:gid:}">Show tickets (<strong>{:ticketcount:}</strong>)</a> &#x029EB; <a href="/ticket/groups/edit/{:gid:}">Modify</a></li>
#}
</ul>