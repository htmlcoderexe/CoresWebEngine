<?php
session_start();
if(!isset($_SESSION['board']))
{
	$_SESSION['board']=Array();
	for($y=0;$y<14;$y++)
	{
		$_SESSION['board'][]=Array();
		for($x=0;$x<14;$x++)
		{
			$_SESSION['board'][$y][$x]=0;
		}
	}
}
if(!isset($_SESSION['player']))
{
	$_SESSION['player']=new Player();
}
if(!isset($_SESSION['enemies']))
{
	$_SESSION['enemies']=Array(new Enemy());
}
function generate_image()
{
	$imgW=514;
	$imgH=514;
	$size=32;
	$stride=5;
	$countX=14;
	$countY=14;
	$img=imagecreatetruecolor($imgW,$imgH);
	for($y=0;$y<$countY;$y++)
	{
		for($x=0;$x<$countX;$x++)
		{
			$xc=($size+$stride)*$x;
			$yc=($size+$stride)*$y;
			$xc2=$xc+$size;
			$yc2=$yc+$size;
			$num=$x+($y*$countX);
			if($x==$_SESSION['player']->X&&$y==$_SESSION['player']->Y)
			{
				imagefilledrectangle($img,$xc,$yc,$xc2,$yc2,0xFF0000);
			}
			if($x==$_SESSION['enemies'][0]->X&&$y==$_SESSION['enemies'][0]->Y)
			{
				imagefilledrectangle($img,$xc,$yc,$xc2,$yc2,0x707070);
			}
			imagerectangle($img,$xc,$yc,$xc2,$yc2,0xFFFFFF);
			
		}
	}	
	header("Content-Type: image/png");
	imagepng($img);
	die;
}


function generate_map()
{
	$map="
	<img src=\"/gametest/board\" usemap=\"#mapname\" />
	<map name=\"mapname\">";
	$imgW=514;
	$imgH=514;
	$size=32;
	$stride=5;
	$countX=14;  
	$countY=14;
	for($y=0;$y<$countY;$y++)
	{
		for($x=0;$x<$countX;$x++)
		{
			$xc=($size+$stride)*$x;
			$yc=($size+$stride)*$y;
			$xc2=$xc+$size;
			$yc2=$yc+$size;
			$num=$x+($y*$countX);
			$map.=" <area shape=\"rect\" coords=\"$xc,$yc,$xc2,$yc2\" href=\"/gametest/go/$x/$y\" alt=\"$num\" title=\"$num\" >";
			//imagerectangle($img,$xc,$yc,$xc2,$yc2,0xFFFFFF);
			
		}
	}	
	$map.="</map>";
	return $map;
}

function ModuleAction_gametest_default($params)
{
	Utility::AddPageContent(generate_map());
}

function ModuleAction_gametest_go($params)
{
	$X=(int)$params[0];
	$Y=(int)$params[1];
	$_SESSION['player']->X=$X;
	$_SESSION['player']->Y=$Y;
	$_SESSION['enemies'][0]->Tick($_SESSION['player']);
	Utility::AddPageContent(generate_map());
	Utility::AddPageContent(Utility::var_dump_ob($_SESSION));
}

function ModuleAction_gametest_board($params)
{
	generate_image();
}