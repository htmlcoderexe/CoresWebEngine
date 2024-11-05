function RollOver(item)
{
	var hintbox=document.querySelector("#hintbox");
	hintbox.innerHTML="<h2>"+item.dataset.itemTitle+"</h2><p>"+item.dataset.itemDesc+"</p>";
}
