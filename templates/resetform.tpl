<form action="/auth/reset" method="POST">
<input type="hidden" name="code" value="{%code|%}" />
{%prompt|Type in your new password, two times%}<br />
<input type="password" name="password1" /><br />
<input type="password" name="password2" /><br />
<button type="submit">Confirm</button>
</form>