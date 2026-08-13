<?php

namespace Controllers;

use Models\Tags\Tag;
use \Route;
use Cores\EngineCore;

/**
 * Description of TagController
 *
 * @author admin
 */
class TagController
{
    #[Route('tag/suggest')]
    public static function SuggestTag($prefixortype='', $prefix ='')
    {
        $evatype = $prefix != '' ? $prefixortype : '';
        $prefix = $prefix == '' ? $prefixortype : $prefix;
        $suggestions = Tag::GetSuggestions($prefix, $evatype);
        EngineCore::EmitJSON($suggestions);
    }
    #[Route('tag/find')]
    public static function FindByTag($tagortype='', $tag ='')
    {
        $evatype = $tag != '' ? $tagortype : '';
        $tag = $tag == '' ? $tagortype : $tag;
        $results = Tag::Find($evatype, $tag);
        EngineCore::EmitJSON($results);
    }
    #[Route('tag/add')]
    public static function AddTag($type='', $id=0)
    {
        $id = intval($id);
        $def = ['tag'=>''];
        $sub = EngineCore::GetSubmission($def);
        if(!EngineCore::IsPOST() || !$sub||$sub['tag']==''||$type==''||$id==0)
        {
            return EngineCore::Error(400);
        }
        if(!EngineCore::CheckPermission("tag.super"))
        {

            return EngineCore::Error(401);
        }
        if(Tag::Attach($id,$sub['tag'],$type))
        {
            return EngineCore::Error(201);
        }
        return EngineCore::Error(304);
    }
    #[Route('tag/get')]
    public static function GetTag($id=0)
    {       
        $results = Tag::GetTags($id);
        EngineCore::EmitJSON($results);
    }
}
