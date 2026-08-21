{%greeting|%}
<br />
<h2>User info:</h2>
{#ifeq|{%self|false%}|true|
Hello, {%username%}. Would you like to make the change today?<br />
<a style="text-decoration:underline" href="/userpanel/edit">edit your profile</a>
#}
<p>
<strong>{%firstname|XXXXXXX%} </strong> {%lastname|XXXXXXXXX%} (<a href="/user/view/{%id|0%}">@{%nickname|xxxxxxx%}</a>)
<br />



</p>