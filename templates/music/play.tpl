{#foreach|{%files%}|
<h2>{:title:}</h2>
<h3>{:duration:} s</h3>
<audio controls>
<source src="/files/stream/{:blobid:}/{:blobid:}.mp3" type="audio/mpeg" /> 
</audio>
#}