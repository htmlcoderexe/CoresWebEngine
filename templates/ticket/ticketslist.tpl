 <a href="/ticket/submit/">Submit a ticket</a><br />
 <a href="/ticket/groups/all">Show groups</a><br />
<table class="ticket-ticketlist sortable" style="width:100%">
    <thead>
    <tr>
        <th>Number</th>
        <th>Short description</th>
        <th>Caller</th>
        <th>Date</th>
        <th>Status</th>
    </tr>
    </thead><tbody>
    {#foreach|{%tickets%}|<tr>
        <td><a href="/ticket/view/{:ticketNumber:}">{:ticketNumber:}</a></td>
        <td>{:title:}</td>
        <td>{#userinfo|username|{:subject:}#}</td>
        <td data-timestamp="{:time:}">{#date|Y-m-d h:i:s|{:time:}#}</td>
        <td>{:status:}</td>
    </tr>#}
    </tbody>
</table>