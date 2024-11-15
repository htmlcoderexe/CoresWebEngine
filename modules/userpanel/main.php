<?php
require "Userpanel.php";

function ModuleAction_userpanel_default($params)
{
	EngineCore::SetPageTitle("User panel");
	Userpanel::DisplayUser();
}

function ModuleAction_userpanel_view($params)
{
	EngineCore::SetPageTitle("View user");
	$id=$params[0];
	Userpanel::DisplayUser(new User(User::GetUsername($id)));
}

function ModuleAction_userpanel_edit($params)
{
	//yo yo yo, testing, 1-2-3!
	EngineCore::SetPageTitle("Change details");
	$id=$params[0];
	$SelectedUser=new User(User::GetUsername($id));
	$e=new TemplateProcessor("userprops");
	EngineCore::AddPageContent($e->process(true));
	
}
 
function ModuleAction_userpanel_property($params)
{
	$property=EngineCore::POST('property','____invalid');
	$value=EngineCore::POST('value','____invalid');
	$user=User::GetCurrentUser();
	UserExtendedProps::SetOneProperty($user,$property,$value);
	echo UserExtendedProps::GetOneProperty($user,$property);
	die;
}

function ModuleAction_userpanel_signup($params)
{
	EngineCore::SetPageContent("Pool is open!");
}

function ModuleAction_userpanel_recover($params)
{
	EngineCore::SetPageContent("Pool is open!");
}

