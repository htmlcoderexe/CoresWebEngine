<form action="/auth/activate" method="POST">
<input type="hidden" name="username" value="{%username|%}" />
{%prompt|Enter password to confirm%}
<input type="password" name="password" />
<button type="submit">Activate!</button>
</form>