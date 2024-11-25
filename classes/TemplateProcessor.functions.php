<?php

global $lipsum;
$lipsum = <<<LIPSUM


Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut ut vulputate ex. Quisque quis turpis rutrum, laoreet erat sed, pharetra massa. In ut risus at turpis porttitor placerat. Duis viverra commodo nisl vel gravida. Vivamus maximus quam vitae efficitur consectetur. Sed non ipsum elementum, efficitur nulla id, condimentum sem. Cras accumsan elit urna, imperdiet tristique nisl sollicitudin id. Etiam a convallis nunc. Curabitur ac nisi quam. Quisque id dapibus purus, nec interdum libero. Morbi faucibus faucibus dictum. Cras vitae lectus eu purus porttitor pretium. Cras tempor sem condimentum velit elementum, pharetra dictum ex consectetur. Sed malesuada congue sapien.

Nunc tempus at est et accumsan. Ut sed ipsum fringilla, ultrices enim nec, eleifend lacus. Proin molestie vestibulum sollicitudin. Nullam sit amet dui pretium est pellentesque sodales. Nam eleifend rhoncus leo sed pharetra. Nam ut nulla gravida, cursus urna sit amet, euismod tellus. Fusce nec tellus dolor. Etiam urna quam, tempus in libero ac, cursus tincidunt ante. Praesent feugiat fringilla molestie. Etiam eget egestas sem. Mauris nunc lectus, pulvinar sed auctor et, tempor a elit. Integer pharetra eget ipsum scelerisque pretium. Vestibulum ex nulla, vulputate vitae facilisis eget, dapibus consequat risus. Morbi non tellus vitae ipsum finibus lacinia non ut nibh. Praesent. 
LIPSUM;
$lipsum = "<h1>404</h1><p>There's nothing here. <a href=\"/\">Go to main page?</a>";

function TemplateProcessorBuiltin_if($cond, $truepart, $falsepart = "")
{
    if($cond == "true")
    {
        return $truepart;
    }
    return $falsepart;
}

function TemplateProcessorBuiltin_ifset($cond, $truepart, $falsepart = "")
{
    if($cond != "")
    {
        return $truepart;
    }
    return $falsepart;
}

function TemplateProcessorBuiltin_ifnonzero($cond, $truepart, $falsepart = "")
{
    if($cond != 0)
    {
        return $truepart;
    }
    return $falsepart;
}

function TemplateProcessorBuiltin_svar($var, $default = "undefined")
{
    if(!isset($_SESSION[$var]))
        return $default;
    return $_SESSION[$var];
}

function TemplateProcessorBuiltin_gvar($var, $default = "undefined")
{
    if(!isset($_GET[$var]))
        return $default;
    return $_GET[$var];
}

function TemplateProcessorBuiltin_pvar($var, $default = "undefined")
{
    if(!isset($_POST[$var]))
        return $default;
    return $_POST[$var];
}

function TemplateProcessorBuiltin_lipsum($charcount = 100)
{
    global $lipsum;
    return substr($lipsum, 0, $charcount);
}

function TemplateProcessorBuiltin_maptodb($template, $query, $fallbacktemplate = "")
{
    return "Unimplemented!";
    $tpl = new TemplateProcessor($template);
    $data = DBHelper::GetArray($query);
    $buffer = "";
    if($fallbacktemplate != "" && count($data) == 0)
    {
        $tpl = new TemplateProcessor($fallbacktemplate);
        return $tpl->process(true);
    }
    for($i = 0; $i < count($data); $i++)
    {
        $tpl->tokens = array_merge($tpl->tokens, $data[$i]);
        $buffer .= $tpl->process(true);
        $tpl->reset();
    }
    return $buffer;
}

function TemplateProcessorBuiltin_isloggedin()
{
    return (User::GetCurrentUser()->username != "Guest") ? "true" : "false";
}


function TemplateProcessorBuiltin_userinfo($param,$uid=-1)
{
    $user = $uid==-1?User::GetCurrentUser():new User(User::GetUsername( $uid));
    switch($param)
    {
        case "username":
            {
                return $user->username;
            }
        case "userid":
            {
                return $user->userid;
            }
        default:
            {
                //return "idk lol";
                return UserExtendedProps::GetOneProperty($user, $param);
            }
    }
}

function TemplateProcessorBuiltin_date($format,$date=-1)
{
    if($date==-1)
    {
        $date=time();
    }
    return date($format,intval($date));
}


function TemplateProcessorBuiltin_ifpermission($permission)
{
    $user = User::GetCurrentUser();
    if($user->HasPermission($permission))
    {
        return "true";
    }
    return "false";
}

function TemplateProcessorBuiltin_baseuri()
{
    return BASE_URI;
}

function TemplateProcessorBuiltin_datee($date, $format = "d-F-Y H:i:s")
{
    if($date == "")
        $date = time();
    return date($format, $date);
}

function TemplateProcessorBuiltin_debuginfo()
{
    if(EngineCore::$DEBUG)
    {
        return EngineCore::$DebugInfo;
    }
    return "";
}

function TemplateProcessorBuiltin_count($end,$start=1,$step=1,$width=1)
{
    $output=[];
    for($i=$start;$i<=$end;$i+=$step)
    {
        $output[]=sprintf("%0".intval($width)."d",$i);
    }
    return $output;
}
