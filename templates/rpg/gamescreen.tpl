<link rel="stylesheet" href="/css/rpg/main.css" />
<div id="gamecontainer">
	<div id="countercontainer">
		<span class="counter" id="counter-megacoins" title="Megacoins">{%megacoins|NaN%}</span>
		<span class="counter" id="counter-emeralds" title="Emeralds">{%emeralds|NaN%}</span>
		<a id="exitlink" href="/rpg/selectcharacter">Exit</a>
	</div>
	{{clear}}
	{{clear}}
	<div id="charstatus">
		<span id="portrait"><img src="/images-site/rpg/portraits/{%class|0%}-{%face|0%}.png" width="128" height="128" /></span>
		<span id="statusbar_container">
			<span class="bar_bg">
				<span class="bar_slider" style="width: 96%;background-color: green">&nbsp;</span>
			</span>
			<span class="bar_bg">
				<span class="bar_slider" style="width: 64%;background-color: #1589FF">&nbsp;</span>
			</span>
		</span>
	</div>
</div>