<a href="/pixdb/">Main page</a>
<br />
<a href="/pixdb/upload">Upload new image</a>
<br />
<br />
<div class="extra_text">{%extra_text|%}</div>
<span class="user_error">{#foreach|{#errors|error#}|{:*:}<br />#}</span><br />
<div class="picturelist">
    {#foreach|{%pictures%}|
<div class="thumbnail">
    <a href="/pixdb/showpic/{:id:}"><img src="/files/stream/{:thumbnail_blob_id:}/{:blob_id:}_thumbnail.png" width="{:thumb_width:}" height="{:thumb_height:}" /></a>
</div>#}
</div>
