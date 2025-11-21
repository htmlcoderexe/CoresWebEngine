
<h3>{%error|%}</h3>
<form action ="/calender/type/update/" method ="POST">
    <label for="name">Name</label><br />
    <input name="name" id ="name" type ="text" value="{%name|%}"/>
    <br />
    <label for="tagcolour">Colour in schedule:</label><br />
    <input name="tagcolour" id ="tagcolour" type ="color"  value="{%marker_colour|%}"/>
    <br />
    <label for="agendacolour">Colour in schedule:</label><br />
    <input name="agendacolour" id ="agendacolour" type ="color"  value="{%agenda_colour|%}"/>
    <br />
    <label for="bgcolour">Background colour in calendar:</label><br />
    <input name="bgcolour" id ="bgcolour" type ="color"  value="{%bg_colour|%}"/>
    <br />
    <label for="numcolour">Number colour in calendar:</label><br />
    <input name="numcolour" id ="numcolour" type ="color"  value="{%number_colour|%}"/>
    <br />
    <input name ="create" type="hidden" value ="true" /><br />
    <input name ="TypeID" type ="hidden" value ="{%id|-1%}" />
    <button type="submit">Save</button>
</form>

