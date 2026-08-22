<h2>Group management</h2>
<p>
    <a href="/cpanel/">🔙 Go Back</a>
</p>{#if|{#ifpermission|group.create#}|<a href="/cpanel/groups/create">New group...</a>#}
<table class="sortable">
    <thead>
        <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Members</th>
        </tr>
    </thead>
    <tbody>
    {#foreach|{%groups%}|
<tr>
    <td><a href="/cpanel/groups/edit/{:id:}">{:name:}</a></td>
    <td>{:type:}</td>
    <td>{:count:}</td>
</tr>

#}</tbody>
</table>