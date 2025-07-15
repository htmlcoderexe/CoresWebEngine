<?php

function ModuleAction_auth_logout($params)
{
	User::LogOut();
	EngineCore::FromWhenceYouCame();
}

function ModuleAction_auth_login($params)
{
	User::LogIn(EngineCore::POST('username'),EngineCore::POST('password'));
	EngineCore::FromWhenceYouCame();
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
					EngineCore::SetPageTitle("Sign up");
					EngineCore::SetPageContent($t->process(true));
				}
				//Utility::AddPageContent(Utility::var_dump_ob($_POST));
				break;
			}
		}
	}
	else
	{
		EngineCore::SetPageTitle("Sign up");
		EngineCore::SetPageContent((new TemplateProcessor("newuser"))->process(true));
	}
}

function ModuleAction_auth_activate($params)
{
	if(isset($params[0]) && $params[0]!="")
	{
		$code=mysql_real_escape_string($params[0]);
                $q=DBHelper::Select('user_activation',['user_id'],['code'=>$code]);
		$results=DBHelper::RunList($q,[$code]);
		if(count($results)!=0)
		{
			$user=new User(User::GetUsername($results[0]));
			$autpl=new TemplateProcessor("activation_confirm");
			$autpl->tokens['username']=$user->username;
			EngineCore::SetPageContent($autpl->process(true));
			$_SESSION['acode']=$code;
		}
		else
		{
			EngineCore::SetPageContent("Invalid activation code.");
		}
	}
	else
	{
		if(isset($_POST['username']) && isset($_POST['password']))
		{
			if(User::LogIn(EngineCore::POST('username'),EngineCore::POST('password'),true) && isset($_SESSION['acode']))
			{
				$code=mysql_real_escape_string($_SESSION['acode']);
				unset($_SESSION['acode']);
				DBHelper::Delete("user_activation",['code'=>$code]);
                                (new User(EngineCore::POST('username')))->Enable();
				EngineCore::GTFO("/");
				
			}
			else
			{
				EngineCore::FromWhenceYouCame(); //back to login
			}
		}
	}
}

function ModuleAction_auth_created($params)
{
	EngineCore::SetPageTitle("Signup successful");
	EngineCore::SetPageContent((new TemplateProcessor("UserCreateWelcomeMessage"))->process(true));
}

function ModuleAction_auth_recover($params)
{
	if(!isset($_POST['email']))
	{
		EngineCore::AppendTemplate("UserRecoverScreen");
		return;
	}
	$eml=mysql_real_escape_string($_POST['email']);
	$q=DBHelper::Select('userinfo',['user_id'],['mail_address'=>$eml]);
        $row = DBHelper::RunRow($q,[$eml]);
	$uid=$row['user_id'];
	$uname=User::GetUsername($uid);
	if($uname!="Guest")
	{
		$user=new User($uname);
		
		
		
		AuthHelper::RequestRecover($uname,$eml);
	}
	else
	{
		//do nothing but tell the user anyway
	}
	//$t=new TemplateProcessor("");
	//$t->tokens['email']=$eml;
	EngineCore::AppendTemplate("resetrequested,email=$eml");
	
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
			EngineCore::AppendTemplate("resetSuccess");
		}
		else
		{
			EngineCore::FromWhenceYouCame();
		}
	}
	else
	{
		if($code!="")
		{
			EngineCore::AppendTemplate("resetform,code=$code");
		}
		else
		{
			//yeah, whatcha gonna do? Not supposed to reach this during normal usage.
			EngineCore::GTFO("/");
		}
	}
}

function ModuleAction_auth_newpassword($params)
{
	if(isset($_SESSION['newpasswordset']))
	{
		EngineCore::AppendTemplate("newpasswordsuccess");
		unset($_SESSION['newpasswordset']);
		
		return;
	}
	if(isset($_POST['password']))
	{
		$cu=User::GetCurrentUser();
		if(!User::LogIn($cu->username,$_POST['password']))
		{
			EngineCore::AppendTemplate("newpassword");
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
			
			EngineCore::GTFO('/auth/newpassword');
			return;
		}
		EngineCore::AppendTemplate("newpassword");
			return;
	}
	EngineCore::AppendTemplate("newpassword");
}