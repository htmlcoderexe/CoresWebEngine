
{#foreach|{%files%}|
<h2>{:title:}</h2>
<h3>{:duration:} s</h3>
<audio id="player" controls>
<source src="/files/stream/{:blobid:}/{:blobid:}.mp3" type="audio/mpeg" /> 
</audio>
#}
{{music/player}}
<script type="text/javascript">
//PlayerEnqueue({%id%}, true);    
</script>