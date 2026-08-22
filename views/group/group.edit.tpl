<a href="/cpanel/groups/list">Back to group list</a><br />
<form action="/cpanel/groups/save" method="POST">{#CSRF#}
    <label for="gname">Group name: </label><br /><input name="gname" id="gname" value="{%name|%}"/><br />
    <label for="gtype">Group type: </label><br /><select name="gtype" id="gtype">
        {#foreach|{%types%}|<option value="{:code:}" {#ifeq|{:code:}|{%type|0%}|selected="selected"#}>{:name:}</option>#}
    </select><br />
    {#ifset|owner|<span>Group owner:</span><br />
    <span>{%owner%}{:username:}{#ifeq|{#userinfo|username#}|{:username:}| (that's you!)#}</span><br />#}
    <label for="gdesc">Description:</label><br />
    <textarea name="gdesc" id="gdesc">{%description|%}</textarea><br />
    <input type="hidden" name="ownerid" value="{#ifset|owner|{:userid:}|-1#}" />
    <input type="hidden" name="gid" value="{%gid|-1%}" />
    <button type="submit">Save</button>
</form>
    {#foreach|{#errors#}|{:*:}#}
    {#ifset|members|<h3>Members</h3>#}
{#ifset|adduser|<form action="/cpanel/groups/adduser" method="POST">{#CSRF#}<input name="gid" type="hidden" value="{%gid%}" /><input name="username" /><button type="submit">➕</button></form>#}
{#ifset|members|{#foreach|{%members%}|<form action ="/cpanel/groups/removeuser" method="POST">{#CSRF#}
<a href="/user/view/{:uid:}">{:username:}</a><input name="gid" type="hidden" value="{%gid%}" /><input name="username" type="hidden" value="{:username:}" /><button type="submit">❌</button>
</form>#}|#}