<a href="/ticket/list/">Back to index</a> 
<form action ="/ticket/{%verb|submit%}" method ="POST">
    <label for="title">Short description</label><br />
    <input name="title" id ="title" type ="text" value="{%title|%}"/><br />
    <select name="ticket_group">
        {#foreach|{%groups%}|<option value="{:object_id:}" {#ifeq|{:object_id:}|{%group_id%}|selected="selected"#}>{:value:}</option>#}
    </select><br />
    <label for="description">Long description</label><br />
    <textarea name="description" id ="description">{%description|%}</textarea>
    <input name ="submit" type="hidden" value ="true" /><br />
    <input name ="EventID" type ="hidden" value ="{%eventId|-1%}" />
    <button type="submit">Save</button>
</form>