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

function displaySuggestions(recipient, suggestions)
{
    
}

function doSuggest()
{
    
    let suggestURL = this.dataset.suggestionsource;
    
    var target =this;
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
    ajax.open("GET",suggestURL+encodeURIComponent(this.value),true);
    ajax.send(null);
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
}

window.addEventListener("load",AttachPostUpdate);

function setCookie(cname, cvalue, exdays) {
  const d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  let expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

setCookie("timeoffset",-((new Date()).getTimezoneOffset()*60),5);