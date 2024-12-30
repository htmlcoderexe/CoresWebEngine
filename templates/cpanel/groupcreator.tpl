<form action="cpanel/group/create" method="POST">
    <label for="gname">Group name</label><input name="gname" id="gname" /><br />
    <label for="gtype">Group type</label><select name="gtype" id="gtype">
        <option value="0">Organisation</option>
        <option value="1">Functional</option>
        <option value="2">Role</option>
        <option value="3">Special</option>
    </select><br />
    <button type="submit">Save</button>
</form>