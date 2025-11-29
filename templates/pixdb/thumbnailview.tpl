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
    <img src="/files/stream/{:blob_id:}/{:blob_id:}.{:extension:}" style="display:none" />
    <a href="/pixdb/showpic/{:id:}" data-imageid="{:id:}" onclick="showImage(event, {:id:});"><img src="/files/stream/{:thumbnail_blob_id:}/{:blob_id:}_thumbnail.png" width="{:thumb_width:}" height="{:thumb_height:}" /></a>
</div>#}
</div>
<div class="lightbox" style="visibility:hidden">
    <a href="#" id="lightbox_prev" onclick="showPrev(event)">&nbsp;</a>
    <a href="#" id="lightbox_next" onclick="showNext(event)">&nbsp;</a>
    <a href="#" id="lightbox_close" onclick="showExit(event)">✕</a>
    <a href="#" id="lightbox_clickthru">◲</a>
    <img class="singleimage" /></div>
<script type="text/javascript">
var imagelist = [];
var captionlist = [];
var offset =0;
{#foreach|{%pictures%}|
imagelist.push({ id: {:id:}, src: "/files/stream/{:blob_id:}/{:blob_id:}.{:extension:}" });#}
    function showImage(e, id)
    {
        console.log("showing image ID", id);
        offset = imagelist.findIndex((idx)=>idx.id == id);
        if(offset === -1)
        {
            return;
        }
        swapImage(e, offset);
        e.preventDefault();
    }
    function swapImage(e, offset)
    {
        console.log("swapping to offset", offset);
        console.log(imagelist);
        document.querySelector(".lightbox").style.visibility = "visible";
        document.querySelector(".lightbox img").src = imagelist[offset].src;
        document.querySelector("#lightbox_clickthru").href = "/pixdb/showpic/"+imagelist[offset].id;
        document.body.style.overflow ="hidden";
    }
    function showNext(e)
    {
        if(offset++ >=imagelist.length)
        {
            offset = 0;
        }
        swapImage(e,offset);
    }
    function showPrev(e)
    {
        if(offset-- <0)
        {
            offset = imagelist.length-1;
        }
        swapImage(e,offset);
    }
    function showExit(e)
    {
        document.querySelector(".lightbox").style.visibility = "hidden";
        document.body.style.overflow ="scroll";
        e.preventDefault();
    }
</script>