function PingPongTheDingDong(element)
{
	var ajax=new XMLHttpRequest();
	var target=element;
	var indicator=element.nextElementSibling;
	ajax.onreadystatechange=function ()
	{
            if (ajax.readyState === 4)
                if (ajax.status === 200 && ajax.status < 300)  
                {
                    indicator.src="/images-site/null.png";
                    element.value = ajax.responseText;
                }
	};
	indicator.src="/images-site/loading.gif";
	ajax.open("POST","/userpanel/property",true);
	ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajax.send("property="+encodeURIComponent(element.name)+"&value="+encodeURIComponent(element.value));
	
	//alert(""); 
}

function SetValue(element,value)
{
    
}

function ResetValue(element)
{
    
}

function SaveValue(element)
{
    
}

function DoPostUpdate()
{
    // do not post if it's a radio button that got unchecked
    if(this.tagName==="input"&& this.type==="radio" && this.checked===false)
    {
        return;
    }
    let TargetProperty=this.name;
    let TargetEndpoint=this.dataset.endpoint;
    let value = this.value;
    if(this.tagName==="input" && this.type==="checkbox")
    this.dataset.postState="updating";
    var target =this;
    target.dataset.postState="updating";
    let ajax = new XMLHttpRequest();
    ajax.onreadystatechange=function()
    {
        if(ajax.readyState===4)
        {
            if(ajax.status === 200)
            {
                try
                {
                    var result = JSON.parse(ajax.responseText);
                    var responseCode = result.responseCode;
                    var value = result.responseValue;
                    switch(responseCode)
                    {
                        case "OK":
                        {
                            target.dataset.postState="ready";
                            SetValue(target,value);
                            SaveValue(target);
                            break;
                        }
                        case "Denied":
                        {
                            target.dataset.postState="error-access";
                            ResetValue(target);
                            break;
                        }
                        case "NotFound":
                        {
                            target.dataset.postState="error-input";
                            ResetValue(target);
                            break;
                        }
                        default:
                        {
                            target.dataset.postState="error-input";
                            break;
                        }
                    }
                }
                catch(error)
                {
                    target.dataset.postState="error-server";
                }
                
            }
            else
            {
                target.dataset.postState="error-server";
            }
        }
    };
    ajax.open("POST",TargetEndpoint,true);
    ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    ajax.send("property="+encodeURIComponent(TargetProperty)+"&value="+encodeURIComponent(value));
}


function dismissSuggestions()
{
    var suggestbox = document.getElementById("suggestbox");
    if(!suggestbox)
    {
        return;
    }
    suggestbox.parentNode.removeChild(suggestbox);
}

function displaySuggestions(recipient, suggestions)
{
    console.log(suggestions);
    dismissSuggestions();
    let suggestbox = document.createElement("div");
    suggestbox.setAttribute("class","suggestable_box");
    suggestbox.setAttribute("id","suggestbox");
    recipient.parentNode.appendChild(suggestbox);
    for(i =0; i< suggestions.length;i++)
    {
        let suggestion = suggestions[i];
        console.log(suggestion);
        let entry = document.createElement("a");
        entry.setAttribute("class","box_suggestion");
        entry.setAttribute("href","#");
        entry.dataset.index = i;
        entry.innerText = suggestion;
        entry.addEventListener("click",()=>{
           recipient.value = entry.innerText;
           dismissSuggestions();
           console.log(entry.innerText);
        });
        suggestbox.appendChild(entry);
        suggestbox.dataset.count = suggestions.length;
        suggestbox.dataset.selectedIndex = -1;
        
    }
    
}



function doKeyboardNav(e)
{
    var suggestbox = document.getElementById("suggestbox");
    if(!suggestbox)
    {
        if(e.keyCode === 40)
        {
            doSuggest(e.target);
        }
        return;
    }
    var current = suggestbox.dataset.selectedIndex;
    var maxval = suggestbox.dataset.count;
    var hitEnter = false;
    switch(e.keyCode)
    {
        case 40: // down
        {
            current++;
            break;
        }
        case 38: // up
        {
            current--;
            break;
        }
        case 13: // enter
        {
            hitEnter = true;
            e.preventDefault();
        }
    }
    // clamp between -1 and maximum index
    current = Math.max(-1, Math.min(current, maxval-1));
    console.log(current);
    if(current != -1)
    {
        document.querySelectorAll(".suggestable_box a").forEach((e)=>{
            if(e.dataset.index == current)
            {
                e.classList.add("selected_suggestion");
                if(hitEnter)
                {
                    e.click();
                    return;
                }
            }
            else
            {
                e.classList.remove("selected_suggestion");
            }
            
        });
        suggestbox.dataset.selectedIndex = current;
    }
    else
    {
        dismissSuggestions();
    }
    
}

function doSuggest(target)
{
    let suggestbox = document.getElementById("suggestbox");
    let suggestURL = target.dataset.suggestionsource;
    
    let ajax = new XMLHttpRequest();
    ajax.onreadystatechange=function()
    {
        if(ajax.readyState===4)
        {
            if(ajax.status === 200)
            {
                try
                {
                    var result = JSON.parse(ajax.responseText);
                    displaySuggestions(target, result);
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
    ajax.open("GET",suggestURL+encodeURIComponent(target.value),true);
    ajax.send(null);
}


function removeTag(tag,inputname)
{
    var elements = document.querySelectorAll('[data-tag'+inputname+'="'+tag+'"]');
    elements.forEach((e)=>{e.parentNode.removeChild(e);});
}

function addTag(inputname)
{
    let id = 'tag_input_' + inputname;
    console.log(id);
    var source = document.getElementById(id);
    if(!source)
    {
        return;
    }
    var tag = source.value;
    if(tag="")
    {
        return;
    }
    console.log(tag);
    var container = document.getElementById("tags_container_"+inputname);
    var newlink = document.createElement("a");
    newlink.setAttribute("href", "#"+tag);
    newlink.dataset.tag = tag;
    newlink.addEventListener("click",(e)=>{removeTag(tag)});
    var forminput = document.createElement("input");
    forminput.setAttribute("type","hidden");
    forminput.value = tag;
    forminput.dataset['tag'+inputname] = tag;
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

function AttachPostUpdate()
{
    document.querySelectorAll("input[data-endpoint], textarea[data-endpoint]").forEach((e)=>{
        e.addEventListener("click",()=>{this.readonly=false;});
        e.addEventListener("focus",()=>{this.readonly=false;});
        e.addEventListener("blur",()=>{this.readonly=true;});
        e.addEventListener("blur",DoPostUpdate);
        e.readonly=true;
    });
    console.log("attached post update");
    document.addEventListener("click",()=>{dismissSuggestions();});
    
}

window.addEventListener("load",AttachPostUpdate);

function setCookie(cname, cvalue, exdays) {
  const d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  let expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

setCookie("timeoffset",-((new Date()).getTimezoneOffset()*60),5);