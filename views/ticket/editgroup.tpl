<a href="/tickets/groups/all">&#x021D0;Back</a><br />
<h2>{%header|Create a new group%}</h2>
{#ifset|error|<span class="user_error">{%error%}</span>#}
<form action="/tickets/groups/submit" method="POST">
    {#CSRF#}
    <label for="name" class="formlabel">Group name: </label>
    <input name="name" id="name" value="{%name|%}" /><br />
    <label for="func_group" class="formlabel">User group:</label>
    <select name="func_group" id="func_group">
        <option value="-1">Create automatically</option>
        {#foreach|{%groups%}|<option value="{:id:}" {#ifeq|{:id:}|{%func_group%}|selected="selected"#}>{:name:}</option>#}
    </select>
    <h3>Description:</h3>
    <textarea name="description">{%description|%}</textarea><br />
    <input type="hidden" name="id" value="{%id|-1%}" />
    <button type="submit">Save</button>
</form>
