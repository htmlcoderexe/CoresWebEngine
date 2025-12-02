<a href="/pixdb/">Main page</a>
<br />
<a href="/pixdb/upload">Upload new image</a>
<br />
<br />
<div class="extra_text">{%extra_text|%}</div>
<span class="user_error">{#foreach|{#errors|error#}|{:*:}<br />#}</span><br />
<button onclick="toggleManagerMode()">Manage</button>
<div class="picturelist">
    {#foreach|{%pictures%}|
<div class="thumbnail" data-selected="false">
    <img src="/files/stream/{:blobid:}/{:blobid:}.{:extension:}" style="display:none" />
    <a href="/pixdb/showpic/{:id:}" data-imageid="{:id:}" onclick="itemClicked(event, {:id:});"><img src="/files/stream/{:thumbnail:}/{:blobid:}_thumbnail.png" width="{:thumb_width:}" height="{:thumb_height:}" /></a>
</div>#}
<div>
{#ifset|prev|<a href="/pixdb/ingest/view/{%iid%}/{%prev%}">Previous page</a>#}{#ifset|page| <strong>{%page%}</strong> {#ifset|next|<a href="/pixdb/ingest/view/{%iid%}/{%next%}">Next page</a>#}
</div>
</div>
<div id="actionpanel" style="visibility:hidden"><span class="itemscount" onclick="toggleActions();"></span></div>
<div id="actions" data-active="false">
<form action="/pixdb/processbatch" method="POST">
<input type="hidden" name="picids" id="picids" />
<input type="hidden" name="owner" value="{%iid|%}" />
<label for="disassociate">Remove from ingest</label><input type="checkbox" name="disassociate" id="disassociate" /><br />
<h3>Add these tags:</h3>
  {{tagpicker|inputname=tagstoadd}}
<h3>Remove these tags:</h3>
  {{tagpicker|inputname=tagstoremove}}
<label for="doalbum">Add to album ID (WIP):</label><input type="checkbox" name="doalbum" id="doalbum" /><br />
<input name="albumid" id="albumid" type="number" />
<button type="submit" id="gobutton">go</button>
</form>
</div>
<div class="lightbox" style="visibility:hidden">
    <a href="#" id="lightbox_prev" onclick="showPrev(event)">&nbsp;</a>
    <a href="#" id="lightbox_next" onclick="showNext(event)">&nbsp;</a>
    <a href="#" id="lightbox_close" onclick="showExit(event)">✕</a>
    <a href="#" id="lightbox_clickthru">◲</a>
    <img class="singleimage" /></div>
<script type="text/javascript">
var managermode = {#ifset|managermode|true|false#}
var imagelist = [];
var captionlist = [];
var offset =0;
var selection = [];
{#foreach|{%pictures%}|
imagelist.push({ id: {:id:}, src: "/files/stream/{:blobid:}/{:blobid:}.{:extension:}" });#}
    
    function toggleManagerMode(e)
    {
        managermode = !managermode;
    }
    
    function toggleActions(e)
    {
        let ap=document.getElementById("actions");
        ap.dataset.active = !(ap.dataset.active=="true");
    }

    function itemClicked(e,id)
    {
        managermode ? toggleSelect(e,id) : showImage(e, id);
    }

    function toggleSelect(e,id)
    {
        let img = document.querySelector('[data-imageid="'+id+'"]').parentNode;
        console.log(img.dataset.selected);
        // yes, a string
        if(img.dataset.selected=="true")
        {
            img.dataset.selected=false;
            selection = selection.filter(s=>s!=id);
        }
        else
        {
            img.dataset.selected=true;
            selection.push(id);
        }
        console.log(selection);
        if(selection.length>0)
        {
            let actionpanel = document.getElementById('actionpanel');
            let txt_count = selection.length + " item" + (selection.length>1?"s":"") + " selected";
            actionpanel.querySelector(".itemscount").innerText=txt_count;
            actionpanel.style.visibility="visible";
            document.getElementById('gobutton').disabled=false;
            document.getElementById('picids').value = selection.join(",");
        }
        else
        {
        document.getElementById('picids').value = "";
            actionpanel.style.visibility="hidden";
            document.getElementById('gobutton').disabled=true;
        }
        e.preventDefault();
    }
    
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