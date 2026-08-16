<form class="signup" action="/auth/signup/submit" method="POST">
<h2>Sign up</h2>
<span style="color:red">{%error|%}</span>
<table class="formtab">
	<tr>
		<td>Username: </td>
		<td><input value="{%username|%}" name="username" size="26" /></td>
	</tr>
	<tr>
		<td>Password: </td>
		<td><input name="password" type="password" size="26" /></td>
	</tr>
	<tr>
		<td>Password again, for good luck: </td>
		<td><input name="passwordconfirm" type="password" size="26" /></td>
	</tr>
	<tr>
		<td>E-mail:</td>
		<td><input value="{%email|%}" name="email" type="email" size="26" /></td>
	</tr>
	<tr>
		<td>E-mail once more for good luck:</td>
		<td><input value="{%emailconfirm|%}" name="emailconfirm" type="email"	 size="26" /></td>
	</tr>
	<tr>
		<td>Display Name (optional):</td>
		<td><input value="{%nickname|%}" name="nickname" size="26" /></td>
	</tr>
</table>
<button type="submit">Sign up!</button>
</form>