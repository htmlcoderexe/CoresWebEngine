<?php

namespace Controllers;

use Cores\EngineCore;
use PostRoute;
use Route;

/**
 * Description of ControlPanelMenuManager
 *
 */
class ControlPanelMenuManager
{
    #[Route('cpanel/menu/view','cpanel.menu.manage')]
    public static function ShowMenuEditor()
    {
        return ['entity_type'=>'menu/menu.edit','menu'=>EngineCore::GetMenuLinks()];
        
    }
    #[PostRoute('cpanel/menu/update','cpanel.menu.manage')]
    public static function UpdateMenuLink($linkid = -1)
    {        
        $menuitem=EngineCore::GetMenuLink(intval($linkid));
        if(!$menuitem)
        {
            return EngineCore::Error(404,'no such link');
        }
        $prop=EngineCore::POST("property","");
        $value=EngineCore::POST("value","");
        if($prop==="link")
        {
            EngineCore::SetMenuLinkHref($linkid, $value);
            return ['entity_type'=>'menu/itemlink', 'value' => $value];
        }
        if($prop==="text")
        {
            EngineCore::SetMenuLinkText($linkid, $value);
            return ['entity_type'=>'menu/itemtext', 'value' => $value];
        }
        
    }
    #[PostRoute('cpanel/menu/create','cpanel.menu.manage')]
    public static function AddMenuLink()
    {
        $text=EngineCore::POST("text","");
        $link=EngineCore::POST("link","");
        if($text && $link)
        {
            EngineCore::AddMenuLink($link, $text);
        }
        EngineCore::GTFO("/cpanel/menu/view");
    }
    #[PostRoute('cpanel/menu/delete','cpanel.menu.manage')]
    public static function RemoveMenuLink()
    {
        $id=intval(EngineCore::POST("id",""));
        if($id>0)
        {
            EngineCore::DeleteMenuLink($id);
        }         
        EngineCore::GTFO("/cpanel/menu/view");
    }
}
