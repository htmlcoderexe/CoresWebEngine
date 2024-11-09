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

function ModuleAction_debug_file($params)
{
    if(Utility::POST("up","")=="")
    {
        ?>
<form action="/debug/file" method="post" enctype="multipart/form-data">
    <input name="up" type="hidden" value="yes" />
    <input name="fileup" type="file" />
    <button type="submit">fuck</button>

</form>
<?php
    }
    else
    {
        $fileid=File::Upload($_FILES['fileup']);
        if($fileid!=-1)
        {
            var_dump(new EVA($fileid));
        }
        echo FILE::$last_error;
        
        die;
    }
}