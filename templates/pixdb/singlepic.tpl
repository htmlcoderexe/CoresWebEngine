<script type="text/javascript">

function loadTag(tag)
{
    var container = document.getElementById("tags_container_singlepic");
    console.log(container);
    var newlink = document.createElement("a");
    console.log(newlink);
    newlink.setAttribute("href", "/pixdb/tag/"+tag);
    var tagstructure = tag.split(":");
    var tagtype = "";
    var tagbase = tag;
    if(tagstructure.length >1)
    {
        tagtype = tagstructure[0];
        tagbase = tagstructure[1];
    }
    newlink.setAttribute("class", "tag"+tagtype);
    newlink.innerText = tagbase;
    console.log(tagbase);
    container.appendChild(newlink);
    console.log(newlink);
}

function attachTag(id, button)
{
    button.disabled = true;
    var source = document.getElementById(id);
    if(!source)
    {
        return;
    }
    var tag = source.value;
    var tagendpoint ="/main/tag/add/{%id%}";
    let ajax = new XMLHttpRequest();
    ajax.onreadystatechange=function()
    {
        if(ajax.readyState===4)
        {
            button.disabled = false;
            if(ajax.status === 200)
            {
                try
                {
                    var result = JSON.parse(ajax.responseText);
                    var responseCode = result.responseCode;
                    switch(responseCode)
                    {
                        case "OK":
                        {
                            source.value="";
                            loadTag(tag);
                            break;
                        }
                        case "Denied":
                        {
                            break;
                        }
                        case "NotFound":
                        {
                            break;
                        }
                        default:
                        {
                            break;
                        }
                    }
                }
                catch(error)
                {
                   
                }
                
            }
            else
            {
                
            }
        }
    };
    ajax.open("POST",tagendpoint,true);
    ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajax.send("tag="+encodeURIComponent(tag));
}
</script>
<a href="/pixdb/">Back</a>
<h3>{%w%} x {%h%} </h3>
<div class="tags_container" id="tags_container_singlepic">
    {#foreach|{%tags%}|<a class="tag{$tagtype|{:*:}$}" href="/pixdb/tag/{:*:}">{$tagbare|{:*:}$}</a> #}
</div>
<div class="suggestable_input_container">
    <input data-suggestionsource="/main/tag/suggest/" data-evaobject="{%id%}" oninput="doSuggest(this);" onkeydown="doKeyboardNav(event);" onblur="" id="tag_input" name="tag_input" size=20 /><button type="button" onclick="attachTag('tag_input',this);">Add tag</button>
</div>
<br />
<img class="singleimage" src="/files/stream/{%blobid%}/{%blobid%}.{%ext%}" />
<br />
<h3>Image text:</h3>
<form action="/pixdb/showpic/{%id%}" method="POST"><button name="redo_lang" value="redo_lang">Refresh</button></form>
{#ifeq|{%text|%}||<span class="information">Text pending...</span>|#}
<pre class="imagetext">{%text|%}</pre>