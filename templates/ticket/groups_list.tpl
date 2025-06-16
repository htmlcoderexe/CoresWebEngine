<h3>Ticket groups</h3>
<a href="/ticket/groups/create">Create new group</a>
<ul>
{#foreach|{%groups%}|   <li>{:value:} &#x029EB; <a href="/ticket/groups/{:object_id:}">Show tickets</a> &#x029EB; <a href="/ticket/groups/edit/{:object_id:}">Modify</a></li>
#}
</ul>