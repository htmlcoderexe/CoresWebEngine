<script type="text/javascript">

function removeTag(tag)
{
    var elements = document.querySelectorAll('[data-tag="'+tag+'"]');
    elements.forEach((e)=>{e.parentNode.removeChild(e);});
}

function addTag(id, inputname)
{
    
    var source = document.getElementById(id);
    if(!source)
    {
        return;
    }
    var tag = source.value;
    var container = document.getElementById("tags_container");
    var newlink = document.createElement("a");
    newlink.setAttribute("href", "#"+tag);
    newlink.dataset.tag = tag;
    newlink.addEventListener("click",(e)=>{removeTag(tag)});
    var forminput = document.createElement("input");
    forminput.setAttribute("type","hidden");
    forminput.value = tag;
    forminput.dataset.tag = tag;
    forminput.name=inputname + "[]";
    
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
    container.appendChild(newlink);
    container.appendChild(forminput);
    source.value="";
}
</script>
    <div id="tagger">
        
        <div class="suggestable_input_container">
            <input data-suggestionsource="/main/tag/suggest/" oninput="doSuggest(this);" onkeydown="doKeyboardNav(event);" onblur="" id="tag_input" name="tag_input" size=20 /><button type="button" onclick="addTag('tag_input','{%inputname|tag%}');">Add tag</button>
        </div>
        <br />
        <div id="tags_container"></div>
    </div>
 
