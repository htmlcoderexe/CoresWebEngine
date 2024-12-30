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
    <td><a href="/cpanel/group/view/{:id:}">{:name:}</a></td>
    <td>{:type:}</td>
    <td>{:count:}</td>
</tr>

#}</tbody>
</table>