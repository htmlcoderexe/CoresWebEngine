<?php
function tpl_menu_getlinks()
{
	$menu=DBHelper::RunTable("SELECT link,text FROM menulinks",[]);
	$fmtstring="\r\n\t\t<li><a href=\"%s\">%s</a></li>";
	$acc1="";
	$c=count($menu);
	for($i=0;$i<$c;$i++)
	{
		$item=$menu[$i];
		$acc1.=sprintf($fmtstring,BASE_URI.$item['link'],$item['text']);
	} 
	return "\r\n\t<ul>$acc1\r\n\t</ul>";
}