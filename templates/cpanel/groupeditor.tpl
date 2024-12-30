<form action="/cpanel/group/create" method="POST">
    <label for="gname">Group name: </label><input name="gname" id="gname" /><br />
    <label for="gtype">Group type: </label><select name="gtype" id="gtype">
        <option value="0">Organisation</option>
        <option value="1">Functional</option>
        <option value="2">Role</option>
        <option value="3">Special</option>
    </select><br />
    <label for="gdesc">Description:</label><br />
    <textarea name="gdesc" id="gdesc"></textarea><br />
    <button type="submit">Save</button>
</form>
{#ifset|adduser|<form action="/cpanel/group/adduser" method="POST"><input name="gid" type="hidden" value="{%gid%}" /><input name="username" /><button type="submit">➕</button></form>#}
{#ifset|members|{#foreach|{%members%}|<form action ="/cpanel/group/removeuser" method="POST">
<a href="/cpanel/user/view/{:uid:}">{:username:}</a><input name="gid" type="hidden" value="{%gid%}" /><input name="uid" type="hidden" value="{:uid:}" /><button type="submit">❌</button>
</form>#}|#}