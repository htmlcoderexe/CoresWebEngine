<?php
use EngineCore as C;
function ModuleAction_cpanel_settings($params)
{
    
}

function ModuleAction_cpanel_menu($params)
{
    
    $action=$params[0]??"list";
    switch($action)
    {
        default:
        {
            if(!EngineCore::CheckPermission("menu.manager"))
            {
                EngineCore::WriteUserError("Not authorised to use this",1); // TODO error constants
                EngineCore::GTFO("/main/unauthorised");
                die;
            }
            $links = EngineCore::GetMenuLinks();
            $t=new TemplateProcessor("cpanel/menulinkeditor");
            $t->tokens['menu']=$links;
            EngineCore::SetPageContent($t->process(true));
            break;
        }
        case "update":
        {
            if(!EngineCore::CheckPermission("menu.manager"))
            {
                EngineCore::WriteUserError("Not authorised to use this",1); // TODO error constants
                HTTPHeaders::ContentType("text/json");
                echo '{"responseCode": "Denied"}';
                die;
            }
            $id=$params[1]??"";
            $menuitem=C::GetMenuLink($id);
            if(!$menuitem)
            {
                HTTPHeaders::ContentType("text/json");
                echo '{"responseCode": "NotFound"}';
                die;
            }
            $prop=EngineCore::POST("property","");
            $value=C::POST("value","");
            if($prop==="link")
            {
                C::SetMenuLinkHref($id, $value);
            }
            if($prop==="text")
            {
                C::SetMenuLinkText($id, $value);
            }
            HTTPHeaders::ContentType("text/json");
            $prop=addslashes($prop);
            echo '{"responseCode": "OK","responseValue": "'.$prop.'"}';
            die;
        }
        case "create":
        {
            $text=C::POST("text","");
            $link=C::POST("link","");
            if($text && $link)
            {
                C::AddMenuLink($link, $text);
                C::GTFO("/cpanel/menu/");
                die;
            }
        }
        case "delete":
        {
            $id=intval(C::POST("id",""));
            if($id>0)
            {
                C::DeleteMenuLink($id);
            }         
            C::GTFO("/cpanel/menu/");
            die;
        }
            
    }
}