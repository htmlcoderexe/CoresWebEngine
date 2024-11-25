<a href="/ticket/list/">Back to index</a> 
<form action ="/ticket/{%verb|submit%}" method ="POST">
    <label for="title">Short description</label><br />
    <input name="title" id ="title" type ="text" value="{%title|%}"/>
    <br />
    <!--<label for="date">Event date YYYY-MM-DD</label><br />
    <input name="date" id ="date" type ="date" value="{%date|%}" />
    <br />
    <label for="time">Event time HH:MM</label><br />
    <input name="time" id ="time" type ="time"  value="{%startTime|%}"/>
    <br />
    <label for="timeD">Event duration</label><br />
    <input name="timeD" id ="timeD" type ="time" value="{%duration|01:00%}" />
    <br />-->
    <label for="description">Long description</label><br />
    <textarea name="description" id ="description">{%description|%}</textarea>
    <input name ="submit" type="hidden" value ="true" /><br />
    <input name ="EventID" type ="hidden" value ="{%eventId|-1%}" />
    <button type="submit">Save</button>
</form>