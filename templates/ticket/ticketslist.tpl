 <a href="/ticket/submit/">Submit a ticket</a>
<table class="ticket-ticketlist" style="width:100%">
    <tr>
        <th>Number</th>
        <th>Short description</th>
        <th>Caller</th>
        <th>Date</th>
        <th>Status</th>
    </tr>
    {#foreach|{%tickets%}|<tr>
        <td><a href="/ticket/view/{:ticketNumber:}">{:ticketNumber:}</a></td>
        <td>{:title:}</td>
        <td>{#userinfo|username|{:subject:}#}</td>
        <td>{#date|Y-m-d h:i:s|{:time:}#}</td>
        <td>{:status:}</td>
    </tr>#}
</table>