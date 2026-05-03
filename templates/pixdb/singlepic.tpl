<a href="/pixdb/">Back</a>
<h3>{%w%} x {%h%} </h3>
    {{system/tagenable|id={%id%}|type=picture|linkprefix=/pixdb/tag/|boxid=tags_container_singlepic|tags={%tags%}}}

<br />
<img class="singleimage" src="/files/stream/{%blobid%}/{%blobid%}.{%ext%}" />
<br />
<h3>Image text:</h3>
<form action="/pixdb/showpic/{%id%}" method="POST"><button name="redo_lang" value="redo_lang">Refresh</button></form>
{#ifeq|{%text|%}||<span class="information">Text pending...</span>|#}
<pre class="imagetext">{%text|%}</pre>