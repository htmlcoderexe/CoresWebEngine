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