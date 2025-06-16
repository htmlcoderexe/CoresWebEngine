<form action="/ticket/groups/submit" method="POST">
    <label for="gname">Group name: </label><input name="gname" id="gname" value="{%gname|%}" /><br />
    <select name="func_group">
        {#foreach|{%groups%}|<option value="{:id:}" {#ifeq|{:id:}|{%func_group%}|selected="selected"#}>{:name:}</option>#}
    </select>
    <h3>Description:</h3>
    <textarea name="description">{%description|%}</textarea><br />
    <input type="hidden" name="gid" value="{%gid|-1%}" />
    <button type="submit">Save</button>
</form>
