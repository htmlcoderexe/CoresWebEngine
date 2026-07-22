<h2>{%title|%}</h2>
{{system/tagenable|id={%id%}|type=document|linkprefix=/docs/tag/|boxid=tags_container_docs|tags={%tags%}}}
{#ifeq|{%thumbnail|%}|||<img src="/files/stream/{%thumbnail%}/{%thumbnail%}.{%thumbnail_ext%}" /><br />#}
<p>{%description|%}</p><br />
{%files|%}