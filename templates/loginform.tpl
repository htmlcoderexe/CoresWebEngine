	<form action="{%action|/auth/login%}" method="POST">
<table>
{#ifset|loginprompt|<tr><th colspan="2">{%loginprompt|%}(<a class="smalllink" href="/auth/signup">Sign up</a>)</th></tr>|#}{#ifset|aerr|<tr><th colspan="2">{%aerr|%}</th></tr>||#}
<tr><td>{%usernamelabel|Username:%}</td><td><input size="12" type="text" name="username" value="{%username|%}"/></td></tr>
<tr><td>{%passwordlabel|Password:%}</td><td><input size="12" type="password" name="password" value="{%password|%}"/></td></tr>
<tr><th><a class="smalllink" href="/auth/recover">recover password</a></th><th><button type="submit">{%submittext|Log in%}</button></th></tr>
</table>
</form> 