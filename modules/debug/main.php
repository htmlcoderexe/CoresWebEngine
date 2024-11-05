<?php

function ModuleAction_debug_template($params)
{
	$user=User::GetCurrentUser();
	if(!$user->HasPermission("super"))
	{
		Utility::FromWhenceYouCame();
		die;
	}
        if(isset($params[1]))
        {
            $params[0].="/".$params[1];
        }
	$tpl=new TemplateProcessor($params[0]);
	Utility::SetPageContent($tpl->process(true));
}

function ModuleAction_debug_info()
{
	$user=User::GetCurrentUser();
	if(!$user->HasPermission("super"))
	{
		Utility::FromWhenceYouCame();
		die;
	}
	phpinfo();
	die();
}

