<h3>Event types:</h3>
<table class="cal-type-manager" style="width:100%">
    <tr>
        <th>Name</th>
        <th>Colour in calendar</th>
        <th>Colour in schedule</th>
        <th>Background colour in calendar</th>
        <th>Number colour in calendar</th>
        <th>Extra option</th>
    </tr>
{#foreach|{%types%}|<tr>
    <td>{:name:}</td>
    <td style="background-color:{:marker_colour:}">&nbsp;</td>
    <td style="background-color:{:agenda_colour:}">&nbsp;</td>
    <td style="background-color:{:bg_colour:}">&nbsp;</td>
    <td style="background-color:{:number_colour:}">&nbsp;</td>
    <td><a href="/calender/type/edit/{:id:}">Edit</a></td>
</tr>#}
</table>
<a class="ui-button" href="/calender/type/create/">Create new</a>
