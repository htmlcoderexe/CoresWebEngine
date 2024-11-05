<?php
class AuthHelper
{
	public static function ValidateNewUserData($data)
	{
		//public static function Create($username,$password,$nickname,$first,$last,$email,$sex)
		$p=		$data['password'];
		$pc=	$data['passwordconfirm'];
		$u=		$data['username'];
		$e=		$data['email'];
		$ec=	$data['emailconfirm'];
		$n=		$data['nickname'];
		$_SESSION['signupdata']=$data;
		$_SESSION['signuperror']="";
		if($p!=$pc)
		{
			$_SESSION['signuperror']="The passwords are not matching";
			return false;
		}
		if($e!=$ec)
		{
			$_SESSION['signuperror']="The addresses are not matching";
			return false;
		}
		if(strlen($p)<8)
		{
			$_SESSION['signuperror']="Password too short";
			return false;
		}
		if(trim($u)=="")
		{
			$_SESSION['signuperror']="Username is required";
			return false;
		}
		if(trim($e)=="")
		{
			$_SESSION['signuperror']="Bad email";
			return false;
		}
		if(DBHelper::ValueExists("userinfo","mail_address",$e))
		{
			$_SESSION['signuperror']="E-mail already registered. <a href=\"/auth/recover\">Reset password?</a>";
			return false;
		}
		if(trim($n)=="")
		{
			$_SESSION['signuperror']="Empty display name";
			return false;
		}
		$user=new User($u);
		if($user->userid!=-1)
		{
			$_SESSION['signuperror']="user already exists??";
			return false;
		}
		User::Create($u,$p,$n,$e);
		AuthHelper::CreateActivationCode($u,$e);
		Utility::GTFO("/auth/created");
		return true;
		//Utility::FromWhenceYouCame();
	}
	
	public static function CreateAuthCode($username,$email)
	{
		$user=new User($username);
		$values=$user->username.$email.rand().time().rand()."";
		$values=sha1($values,true);
		$scale=(52.0/256.0);
		$letters=array_merge(range('a','z'),range('A','Z'));
		$code="";
		$cues=Array();
		shuffle($letters);
		for($i=0;$i<20;$i++)
		{
			$cu=ord($values[$i])*$scale;
			$cues[]=$cu;
			$code.=$letters[round($cu)];
		}	
		return $code;
	}
	
	public static function CreateActivationCode($username,$email)
	{
		$user=new User($username);
		$code=AuthHelper::CreateAuthCode($username,$email);
		DBHelper::Insert('user_activation',Array(null,$user->userid,$code,time()));
                
                
		$first=UserExtendedProps::GetOneProperty($user,'firstname');
		$subject="Account activation";
		$t=new TemplateProcessor("mail_user_activate,code=$code,first=$first");
		$content=$t->process(true);
                mail($email,$subject,$content);
	}
	public static function ActivateUser($code)
	{
		
	}
	
	public static function RequestRecover($username,$email)
	{
		$user=new User($username);
		$code=AuthHelper::CreateAuthCode($username,$email);
		DBHelper::Insert('user_recovery',Array(null,$user->userid,$code,time()));
		$subject="Password Recovery";
		$t=new TemplateProcessor("mail_reset_password");
		$t->tokens['code']=$code;
		$t->tokens['first']=UserExtendedProps::GetOneProperty($user,'firstname');
		$content=$t->process(true);
		mail($email,$subject,$content);
	}
	public static function ResetUser($code,$password1,$password2)
	{
		if($password1!=$password2)
		{
			return false;
		}
		if(strlen($password1)<6)
		{
			return false;
		}
		$hash=password_hash($password1, PASSWORD_DEFAULT);
                $stmt = DBHelper::$DBLink->prepare("SELECT user_id FROM user_recovery WHERE code=?");
                $stmt->bindParam(1, $code);
		$results=DBHelper::GetList($stmt);
		if(count($results)!=0)
		{
			$uid=(int)$results[0];
			//set the new password
                        $stmt = DBHelper::$DBLink->prepare("UPDATE users SET passwordhash = ? WHERE id = ?");
                        $stmt->bindParam(1,$hash);
                        $stmt->bindParam(2,$uid);
			DBHelper::GetArray($stmt);
			$autpl=new TemplateProcessor("resetSuccess");
			Utility::AddPageContent($autpl->process(true));
			return true;
		}
		else
		{
			return false;
		}
	}
}