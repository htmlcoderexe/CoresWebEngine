<a href="/pixdb/">Back</a>
<h3>{%width%} x {%height%} </h3>
    {{system/tagenable|id={%id%}|type=picture|linkprefix=/pixdb/tag/|boxid=tags_container_singlepic|tags={%tags%}}}

<br />
<img class="singleimage" src="/files/stream/{%blob_id%}/{%blob_id%}.{%extension%}" />
<br />
<h3>Image text:</h3>
<form action="/pixdb/retesseract/{%id%}" method="POST">{#CSRF#}<button name="redo_lang" value="redo_lang">Refresh</button></form>
{#ifeq|{%text|%}||<span class="information">Text pending...</span>|#}
<pre class="imagetext">{%text|%}</pre>