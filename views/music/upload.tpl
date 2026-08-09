<h3>Insert mp3 here:</h3>
<span class="user_error">{#foreach|{#errors|error#}|{:*:}<br />#}</span>
<form action="/music/upload/" method="POST" enctype="multipart/form-data">
    <input name="musicupload" type="file" accept=".mp3" />
    {#CSRF#}
    <button type="submit">Upload</button>
</form>
