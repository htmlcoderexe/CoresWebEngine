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
	Utility::AddPageContent($e->process(true));
	
}
 
function ModuleAction_userpanel_property($params)
{
	$property=Utility::POST('property','____invalid');
	$value=Utility::POST('value','____invalid');
	$user=User::GetCurrentUser();
	UserExtendedProps::SetOneProperty($user,$property,$value);
	echo UserExtendedProps::GetOneProperty($user,$property);
	die;
}

function ModuleAction_userpanel_signup($params)
{
	Utility::SetPageContent("Pool is open!");
}

function ModuleAction_userpanel_recover($params)
{
	Utility::SetPageContent("Pool is open!");
}

