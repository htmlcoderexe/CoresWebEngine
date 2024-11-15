<?php
class Userpanel
{
    public static function DisplayUser($user=null)
    {
        $cu=User::GetCurrentUser();
        $self=false;
        if($user==null)
        {
                $user=$cu;
                $self=true;
            }
	if(!($user)||($user->IsGuest()))
	{
		$content="You are a guest. What are you doing here?";
	}
	else
	{
            $content="";
            if($self || ($user->userid == $cu->userid))
            {
                $content = "Hello, {$user->username}. Would you like to make the change today?";
            }
            $content.="\r\n<br />";
            $content.="\r\n<h2>User info"; 
            $content.="</h2>";
            if($self || ($user->userid == $cu->userid))
            {
                $content .= "<a style=\"text-decoration:underline\" href=\"/userpanel/edit/{$user->userid}\">edit your profile</a>";
            }
            $contax=Array();
            $contax['nickname']=UserExtendedProps::GetOneProperty($user,'nickname');
            $contax['age']=$user->GetAge();
            $contax['id']=$user->userid;
            $t=new TemplateProcessor('usercard');
            $t->tokens=$contax;
            $content.=$t->process(true);	
	}
        EngineCore::SetPageContent($content);
    }
}