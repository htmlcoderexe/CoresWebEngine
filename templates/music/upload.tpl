<h3>Insert mp3 here:</h3>
<span class="user_error">{#foreach|{#errors|error#}|{:*:}<br />#}</span>
<form action="/music/upload/" method="POST" enctype="multipart/form-data">
    <input name="musicupload" type="file" accept=".mp3" />
    <input type="hidden" name="uploading" value="yes" />
    <button type="submit">Upload</button>
</form>
