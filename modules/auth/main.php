<?php

function ModuleAction_auth_logout($params)
{
	User::LogOut();
	Utility::FromWhenceYouCame();
}

function ModuleAction_auth_login($params)
{
	User::LogIn(Utility::POST('username'),Utility::POST('password'));
	Utility::FromWhenceYouCame();
	die();
}

function ModuleAction_auth_signup($params)
{
	if(isset($params[0]) && $params[0]!="")
	{
		switch($params[0])
		{
			case "submit":
			{
				$v=AuthHelper::ValidateNewUserData($_POST);
				if(!$v)
				{
					$t=new TemplateProcessor("newuser");
					$t->tokens=$_SESSION['signupdata'];
					$t->tokens['error']="<strong>Error: </strong>".$_SESSION['signuperror'];
					$t->tokens['s'.$_SESSION['signupdata']['sign']]=' selected="selected"';
					$t->tokens['g'.$_SESSION['signupdata']['sex']]=' selected="selected"';
					Utility::SetPageTitle("Sign up");
					Utility::SetPageContent($t->process(true));
				}
				//Utility::AddPageContent(Utility::var_dump_ob($_POST));
				break;
			}
		}
	}
	else
	{
		Utility::SetPageTitle("Sign up");
		Utility::SetPageContent((new TemplateProcessor("newuser"))->process(true));
	}
}

function ModuleAction_auth_activate($params)
{
	if(isset($params[0]) && $params[0]!="")
	{
		$code=mysql_real_escape_string($params[0]);
		$results=DBHelper::GetList("SELECT user_id FROM user_activation WHERE code='$code'");
		if(count($results)!=0)
		{
			$user=new User(User::GetUsername($results[0]));
			$autpl=new TemplateProcessor("activation_confirm");
			$autpl->tokens['username']=$user->username;
			Utility::SetPageContent($autpl->process(true));
			$_SESSION['acode']=$code;
		}
		else
		{
			Utility::SetPageContent("Invalid activation code.");
		}
	}
	else
	{
		if(isset($_POST['username']) && isset($_POST['password']))
		{
			if(User::LogIn(Utility::POST('username'),Utility::POST('password'),true) && isset($_SESSION['acode']))
			{
				$code=mysql_real_escape_string($_SESSION['acode']);
				unset($_SESSION['acode']);
				DBHelper::GetArray("DELETE FROM user_activation WHERE code='$code'");
				(new User(Utility::POST('username')))->Enable();
				Utility::GTFO("/");
				
			}
			else
			{
				Utility::FromWhenceYouCame(); //back to login
			}
		}
	}
}

function ModuleAction_auth_created($params)
{
	Utility::SetPageTitle("Signup successful");
	Utility::SetPageContent((new TemplateProcessor("UserCreateWelcomeMessage"))->process(true));
}

function ModuleAction_auth_recover($params)
{
	if(!isset($_POST['email']))
	{
		Utility::AddTemplate("UserRecoverScreen");
		return;
	}
	$eml=mysql_real_escape_string($_POST['email']);
	$q=DBHelper::GetOneRow("SELECT user_id 
	FROM userinfo 
	WHERE mail_address='$eml'");
	$uid=$q['user_id'];
	$uname=User::GetUsername($uid);
	if($uname!="Guest")
	{
		$user=new User($uname);
		
		
		
		AuthHelper::RequestRecover($uname,$email);
	}
	else
	{
		//do nothing but tell the user anyway
	}
	//$t=new TemplateProcessor("");
	//$t->tokens['email']=$eml;
	Utility::AddTemplate("resetrequested,email=$eml");
	
}

function ModuleAction_auth_reset($params)
{
	$code=isset($params[0])?$params[0]:"";
	//Utility::AddPageContent($code);
	if(isset($_POST['code']))
	{
		$results=AuthHelper::ResetUser($_POST['code'],$_POST['password1'],$_POST['password2']);
		if($results)
		{
			Utility::AddTemplate("resetSuccess");
		}
		else
		{
			Utility::FromWhenceYouCame();
		}
	}
	else
	{
		if($code!="")
		{
			Utility::AddTemplate("resetform,code=$code");
		}
		else
		{
			//yeah, whatcha gonna do? Not supposed to reach this during normal usage.
			Utility::GTFO("/");
		}
	}
}

function ModuleAction_auth_newpassword($params)
{
	if(isset($_SESSION['newpasswordset']))
	{
		Utility::AddTemplate("newpasswordsuccess");
		unset($_SESSION['newpasswordset']);
		
		return;
	}
	if(isset($_POST['password']))
	{
		$cu=User::GetCurrentUser();
		if(!User::LogIn($cu->username,$_POST['password']))
		{
			Utility::AddTemplate("newpassword");
			return;
		}
		if($_POST['password1']==$_POST['password2'])
		{
			$_SESSION['newpasswordset']=true;
			
			
			$hash=password_hash($_POST['password1'], PASSWORD_DEFAULT);
			$cu=User::GetCurrentUser();
			$uid=$cu->userid;
			//set the new password
                        DBHelper::Update("users", ["passwordhash"=>$hash], ["id"=>$uid]);
			
			Utility::GTFO('/auth/newpassword');
			return;
		}
		Utility::AddTemplate("newpassword");
			return;
	}
	Utility::AddTemplate("newpassword");
}