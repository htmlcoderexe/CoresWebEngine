<form action="/rpg/createcharacter" method="POST">
<span class="error">{%errormessage|%}</span><br />
<strong>Character name:</strong><br />
<input name="charname" maxlength="32" size="16" /><br />
<strong>Character class:</strong>
<input name="charclass" value="0" id="b0" type="radio" /><label for="b0">Brawler</label><input name="charclass" value="1" id="b1" type="radio" /><label for="b1">Mage</label><input name="charclass" value="2" id="b2" type="radio" /><label for="b2">Ranger</label>



<br />
<strong>Character gender, if applicable</strong>
<select name="chargender">
	<option value="0">Unspecified</option>
	<option value="1">Male</option>
	<option value="2">Female</option>
</select>
<br />
<button type="submit">Create and play!</button>
</form>