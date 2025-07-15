<script type="text/javascript">
function checkcount(el)
{
    var upbutt = document.getElementById("uploadbutton");
    var warn = document.getElementById("too_many_warning");
    var count = el.files.length;
    if(count > {%max%})
    {
        upbutt.disabled = true;
        warn.style.display = "block";
    }
    else
    {
        upbutt.disabled = false;
        warn.style.display = "none";
    }
}


function toggletagger(el)
{
    document.getElementById('tagger').style.display = el.checked ? "inherit" : "none";
}
</script>

<h3>Select up to {%max%} images to upload:</h3>
<span class="user_error">{#foreach|{#errors|error#}|{:*:}<br />#}</span>
<form action="/pixdb/uploadbulk/" method="POST" enctype="multipart/form-data">
    <label for="createalbum">Create an album</label><input type="checkbox" name="createalbum" id="createalbum" value="true" /><br />
    <label for="applytags">Apply tags to the images</label><input type="checkbox" name="applytags" id="applytags" value="true" onchange="toggletagger(this);" /><br />
    
    <div id="tagger" style="display:none;">
        {{tagpicker|inputname=new_tags}}
    </div>
    <input name="picupload[]" multiple onchange="checkcount(this);" type="file" accept=".jpg,.jpeg,.png,.gif" />
    <input type="hidden" name="uploading" value="yes" />
    <button id="uploadbutton" type="submit" disabled>Upload</button><br />
    <span id="too_many_warning" style="color:red;visibility:hidden">Too many files, you can upload at most {%max%} at a time.</span>
</form>