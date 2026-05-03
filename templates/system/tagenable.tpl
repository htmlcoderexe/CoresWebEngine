<script type="text/javascript">

function loadTag(tag)
{
    var container = document.getElementById("{%boxid%}");
    console.log(container);
    var newlink = document.createElement("a");
    console.log(newlink);
    newlink.setAttribute("href", "{%linkprefix%}"+tag);
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
    var tagendpoint ="/main/tag/add/{%type%}/{%id%}";
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
    {{system/showtags|boxid={%boxid%}|tags={%tags%}|linkprefix=linkprefix}}


<div class="suggestable_input_container">
    <input data-suggestionsource="/main/tag/suggest/" data-evaobject="{%id%}" oninput="doSuggest(this);" onkeydown="doKeyboardNav(event);" onblur="" id="tag_input" name="tag_input" size=20 /><button type="button" onclick="attachTag('tag_input',this);">Add tag</button>
</div>