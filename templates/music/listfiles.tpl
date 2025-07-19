{#foreach|{%tracks%}|
<div class="musictrack">
<span><a href="/music/play/{:id:}">{#ifeq|{:artist:}|||{:artist:} - #}{:title:}  - {:duration:}</a></span><br />
<audio controls>
<source src="/files/stream/{:blobid:}/{:blobid:}.mp3" type="audio/mpeg" /> 
</audio><a href="/music/toscreen/{:id:}">🔂</a>
</div>
#}
