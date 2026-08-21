<h2>User management</h2>
<p>
    <a href="/cpanel/">🔙 Go Back</a>
</p>
{#if|{#ifpermission|user.create#}|<form action="/cpanel/users/create/" method="POST">{#CSRF#}
Username:<input name="username" id="username" />Password:<input name="password" id="password" type="password" />
<button type="submit">➕</button>
</form>#}
<table class="sortable">
    <thead>
        <tr>
            <th>Username</th>
            <th>Display name</th>
            <th>Disabled?</th>
        </tr>
    </thead>
    <tbody>
    {#foreach|{%users%}|
<tr>
    <td><a href="/user/view/{:id:}">{:username:}</a></td>
    <td>{:nickname:}</td>
    <td>{:disabled:}</td>
</tr>

#}</tbody>
</table>
