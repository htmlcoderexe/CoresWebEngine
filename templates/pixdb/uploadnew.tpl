<h3>Select an image to upload:</h3>
<span class="user_error">{#foreach|{#errors|error#}|{:*:}<br />#}</span>
<form action="/pixdb/upload/" method="POST" enctype="multipart/form-data">
    <input name="picupload" type="file" accept=".jpg,.jpeg,.png,.gif" />
    <input type="hidden" name="uploading" value="yes" />
    <button type="submit">Upload</button>
</form>