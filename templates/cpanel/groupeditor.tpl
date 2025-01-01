<a href="/cpanel/group/list">Back to group list</a><br />
<form action="/cpanel/group/{%verb|create%}" method="POST">
    <label for="gname">Group name: </label><br /><input name="gname" id="gname" value="{%name|%}"/><br />
    <label for="gtype">Group type: </label><br /><select name="gtype" id="gtype">
        {#foreach|{%types%}|<option value="{:code:}" {#ifeq|{:code:}|{%type|0%}|selected="selected"#}>{:name:}</option>#}
    </select><br />
    {#ifset|owner|<span>Group owner:</span><br />
    <span>{%owner%}{:username:}</span><br />#}
    <label for="gdesc">Description:</label><br />
    <textarea name="gdesc" id="gdesc">{%description|%}</textarea><br />
    <input type="hidden" name="ownerid" value="{#ifset|owner|{:userid:}|-1#}" />
    <input type="hidden" name="gid" value="{%gid|-1%}" />
    <button type="submit">Save</button>
</form>
    {#ifset|members|<h3>Members</h3>#}
{#ifset|adduser|<form action="/cpanel/group/adduser" method="POST"><input name="gid" type="hidden" value="{%gid%}" /><input name="username" /><button type="submit">➕</button></form>#}
{#ifset|members|{#foreach|{%members%}|<form action ="/cpanel/group/removeuser" method="POST">
<a href="/cpanel/user/view/{:uid:}">{:username:}</a><input name="gid" type="hidden" value="{%gid%}" /><input name="uid" type="hidden" value="{:uid:}" /><button type="submit">❌</button>
</form>#}|#}