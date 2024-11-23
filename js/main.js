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

function setCookie(cname, cvalue, exdays) {
  const d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  let expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

setCookie("timeoffset",-((new Date()).getTimezoneOffset()*60),5);