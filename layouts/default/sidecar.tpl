<div class="box">
			<div class="boxheader">
			{#ifset|headlink|<a href="{%headlink%}">|#}
				{%header|&nbsp;%}
				{#ifset|headlink|</a>|#}
			</div>
			<div class="boxbody">
			{%content|{#lipsum|500#}%}
			</div>
		</div> 