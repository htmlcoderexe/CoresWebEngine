<a href="/music/pcommand/play">▶</a> | <a href="/music/pcommand/pause">⏸</a> | <a href="/music/pcommand/voldown">🔉</a> | <a href="/music/pcommand/volup">🔊</a> 

<br />
<a href="/music/upload">Upload</a><br />
{#foreach|{%tracks%}|
<div class="musictrack">
<span><a href="/music/play/{:id:}">{#ifeq|{:artist:}|||{:artist:} - #}{:title:}  - {:duration:}</a></span><br />
<audio controls>
<source src="/files/stream/{:blobid:}/{:blobid:}.mp3" type="audio/mpeg" /> 
</audio><a href="/music/toscreen/{:id:}">🔂</a>
</div>
#}
