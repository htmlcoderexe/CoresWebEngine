<h3>Event types:</h3>
<table class="cal-type-manager" style="width:100%">
    <tr>
        <th>Name</th>
        <th>Colour in calendar</th>
        <th>Colour in schedule</th>
        <th>Extra option</th>
    </tr>
{#foreach|{%types%}|<tr>
    <td>{:name:}</td>
    <td style="background-color:{:calendar.tagcolour:}">&nbsp;</td>
    <td style="background-color:{:calendar.agendacolour:}">&nbsp;</td>
    <td><a href="/calender/type/edit/{:id:}">Edit</a></td>
</tr>#}
</table>
<a class="ui-button" href="/calender/type/create/">Create new</a>
