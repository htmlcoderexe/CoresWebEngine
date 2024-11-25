
<h3>{%error|%}</h3>
<form action ="/calender/type/update/" method ="POST">
    <label for="name">Name</label><br />
    <input name="name" id ="name" type ="text" value="{%name|%}"/>
    <br />
    <label for="tagcolour">Colour in schedule:</label><br />
    <input name="tagcolour" id ="tagcolour" type ="color"  value="{%tagcolour|%}"/>
    <br />
    <label for="agendacolour">Colour in schedule:</label><br />
    <input name="agendacolour" id ="agendacolour" type ="color"  value="{%agendacolour|%}"/>
    <br />
    <input name ="create" type="hidden" value ="true" /><br />
    <input name ="TypeID" type ="hidden" value ="{%typeId|-1%}" />
    <button type="submit">Save</button>
</form>

