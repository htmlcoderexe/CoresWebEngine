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
        if($fileid)
        {
            var_dump($fileid);
        }
        echo FILE::$last_error;
        
        die;
    }
}

function ModuleAction_debug_dlfile($params)
{
    $fid=$params[0];
    
    if(isset($_SERVER['HTTP_RANGE']))
    {
        $parsed_range = HTTPHeaders::ParseRangeRequest($_SERVER['HTTP_RANGE']);
        if(!$parsed_range)
        {
            // bad range
            Logger::log("Bad range: ".$_SERVER['HTTP_RANGE']);
            HTTPHeaders::Status(416);
            File::ServeByBlobID($fid);
        }
        else
        {
            Logger::log("Good range: ".$_SERVER['HTTP_RANGE']);
            list($start,$end)=$parsed_range;
            File::ServeByBlobID($fid,$start,$end);
        }
    }
    else
    {
        File::ServeByBlobID($fid);
    }
    
    
}

function ModuleAction_debug_tag($params)
{
    $action=$params[0];
    $tag=$params[1];
    $target=$params[2];
    
    switch($action)
    {
        case "add":
            Tag::Attach($target, $tag);

            break;
        case "remove":
            Tag::Remove($target, $tag);

            break;
        case "find":
            $results=Tag::Find($target, $tag);
            $objects=[];
            foreach($results as $obj)
                $objects[]=new EVA($obj);
            var_dump($objects);
            die();
            break;

        default:
            break;
    }
}