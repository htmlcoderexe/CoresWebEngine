<?php
//TODO: move to an include
function ModuleFunction_rpg_GetCharParam($param,$charid=-1)
{
	$charid=$charid==-1?(int)$_SESSION['rpg.current_char']:$charid;
	//TODO: cache the EVA object
	$character=new EVA($charid);
	return $character->GetSingleAttribute($param);
}

function ModuleFunction_rpg_IsOwner($charid,$userid=-1)
{
	$cu=User::GetCurrentUser();
	$userid=$userid==-1?$cu->userid:$userid;
	EngineCore::Dump2Debug($userid);
	if($userid==-1) //guest
		EngineCore::GTFO("/");
	return (ModuleFunction_rpg_GetCharParam('owner',$charid)==$userid);
}

function ModuleAction_rpg_default()
{
	EngineCore::SetPageContent(file_get_contents(__DIR__."\\itemtest.html"));
	EngineCore::Write2Debug(__DIR__);
}

function ModuleAction_rpg_game()
{
	if(!isset($_SESSION['rpg.current_char']))
		EngineCore::GTFO("/rpg/selectcharacter");
	EngineCore::RawModeOn();
	$screen=new TemplateProcessor("rpg\\gamescreen");
	$emeraldcount=ModuleFunction_rpg_GetCharParam('rpg.emeralds');
	$megacoincount=ModuleFunction_rpg_GetCharParam('rpg.megacoins');
	$screen->tokens=Array(
	'emeralds'=>$emeraldcount,
	'megacoins'=>$megacoincount
	
	
	);
	EngineCore::SetPageContent($screen->process(true));
}

function ModuleAction_rpg_selectcharacter($params)
{
	//Utility::ddump($_SESSION);
	//if there's a param given after the action, assume it's character ID to select
	if(count($params)>0)
	{
		$id=(int)$params[0];
		//check if current user owns the character
		if(ModuleFunction_rpg_IsOwner($id))
		{
			//set current character
			$_SESSION['rpg.current_char']=$id;
			//redirect to the game
			EngineCore::GTFO("/rpg/game");die();
		}
	}
	$cu=User::GetCurrentUser();
	$selector=new TemplateProcessor("rpg\\character.selector");
	$selector_item=new TemplateProcessor("rpg\\character.selector.item");
	$characters=EVA::GetByProperty('owner',$cu->userid,'rpg.character');
	$listbuffer="";
	$c=count($characters);
	$classes=Array("Brawler","Mage","Ranger");
	for($i=0;$i<$c;$i++)
	{
		$selector_item->tokens['id']=$characters[$i];
		$selector_item->tokens['name']=ModuleFunction_rpg_GetCharParam('rpg.character_name',$characters[$i]);
		$selector_item->tokens['class']=$classes[ModuleFunction_rpg_GetCharParam('rpg.character_class',$characters[$i])];
		$selector_item->tokens['level']=ModuleFunction_rpg_GetCharParam('rpg.character_level',$characters[$i]);
		$listbuffer.=$selector_item->process(true);
		$selector_item->reset();
	}
	$selector->tokens['characters']=$listbuffer;
	EngineCore::SetPageContent($selector->process(true));
}

function ModuleAction_rpg_setcharparam()
{
	EngineCore::RawModeOn();
	if(!isset($_SESSION['rpg.current_char']))
		die("hi there");
	$charid=(int)$_SESSION['rpg.current_char'];
	$property=EngineCore::POST('property','____invalid');
	$value=EngineCore::POST('value','____invalid');
	//echo $property." was set to ".$value;die;
	$character=new EVA($charid);
	$user=User::GetCurrentUser();
	if($character->GetSingleAttribute('owner')==$user->userid)
	{
		$character->SetSingleAttribute($property,$value);
		$character->Save();
		die($value);
	}
	die('this is awkward :(');
	
}

function ModuleAction_rpg_getcharparam()
{
	EngineCore::RawModeOn();
	if(!isset($_SESSION['rpg.current_char']))
		die("hi there");
	$charid=(int)$_SESSION['rpg.current_char'];
	$property=EngineCore::POST('property','____invalid');
	$character=new EVA($charid);
	$user=User::GetCurrentUser();
	if($character->GetSingleAttribute('owner')==$user->userid)
	{
		$value=$character->GetAttribute($property);
		die($value);
	}
	die('this is awkward :(');
	
}

function ModuleAction_rpg_createcharacter()
{
	if(!isset($_POST['charname']) || !isset($_POST['charclass']))
	{
		//display character creation form
		$charform=new TemplateProcessor("rpg\\character.creator");
		
		EngineCore::SetPageContent($charform->process(true));
	}
	else
	{
		$name=$_POST['charname'];
		$class=$_POST['charclass'];
		$gender=EngineCore::POST('chargender',0);
		//check if character exists
		$matches=EVA::GetByProperty('rpg.character_name',$name,'rpg.character');
		if(count($matches)==0)
		{
			//create character, redirect to game
			$init=Array("rpg.character_name","rpg.character_class","rpg.emeralds","rpg.megacoins","rpg.location","rpg.character_gender","owner");
			$initbux=1200;
			$initemeralds=10;
			$initloc=0; //starter location
			$character=EVA::CreateObject('rpg.character',$init);
			$character->SetSingleAttribute('rpg.character_name',$name);
			$character->SetSingleAttribute('rpg.character_class',$class);
			$character->SetSingleAttribute('rpg.character_gender',$gender);
			$character->SetSingleAttribute('rpg.megacoins',$initbux);
			$character->SetSingleAttribute('rpg.emeralds',$initemeralds);
			$character->SetSingleAttribute('rpg.location',$initloc);
			$character->SetSingleAttribute('owner',EngineCore::$CurrentUser->userid);
			$character->SetSingleAttribute('rpg.character_exp',0);
			$character->SetSingleAttribute('rpg.character_level',1);
			$character->Save();
			
			//set current character
			$_SESSION['rpg.current_char']=$character->id;
			//redirect to the game
			EngineCore::GTFO("/rpg/game");die();
		}
		else
		{
			//display form + error
			$charform=new TemplateProcessor("rpg\\character.creator");
			$charform->tokens=Array('errormessage'=>'Character with this name already exists');	
			EngineCore::SetPageContent($charform->process(true));
		}
	}
}


function ModuleAction_rpg_icontest($params)
{
	EngineCore::RawModeOn();
	header("Content-Type: image/svg+xml");
//	header("Cache-Control: no-store ");
	header("Expires: Wed, 21 Oct 2015 07:28:00 GMT");
	if(!isset($_SESSION['srand']) || $_SESSION['srand'] >100000)
	{
		$_SESSION['srand']=0;
	}
	srand((int)$_SESSION['srand']);
	if(count($params)<=0)
	{
		EngineCore::GTFO("/rpg/icontest/".rand(10000,99999));
	}
	$_SESSION['srand']+=rand(2,10);
			$icon=new TemplateProcessor("rpg\\svg\\people\\generic_female.svg");
			$colour1="#";
			$colour2="#";
			for($i=0;$i<3;$i++)
			{
				$colour1.=sprintf("%02x",rand(0,255));
				$colour2.=sprintf("%02x",rand(0,255));
			}
			$icon->tokens=Array('eyecolour'=>$colour1,'haircolour'=>$colour2);	
			echo $icon->process(true);
			die;
		
}

function ModuleAction_rpg_icontest2()
{
	$container=new TemplateProcessor("\\rpg\\icontest");
	EngineCore::SetPageContent($container->process(true));
} 