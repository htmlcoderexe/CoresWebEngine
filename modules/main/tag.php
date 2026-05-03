<?php

function ModuleAction_main_tag_suggest($params)
{
    $evatype = count($params) > 1 ? $params[0] : "";
    $prefix = count($params) > 1 ? $params[1] : $params [0];
    $suggestions = Tag::GetSuggestions($prefix, $evatype);
    EngineCore::EmitJSON($suggestions);
}
function ModuleAction_main_tag_find($params)
{
    
    $evatype = count($params) > 1 ? $params[0] : "";
    $tag = count($params) > 1 ? $params[1] : $params [0];
    $results = Tag::Find($evatype,$tag);
    EngineCore::EmitJSON($results);
    
}

function ModuleAction_main_tag_get($params)
{
    $evaid = $params[0];
    $results = Tag::GetTags($evaid);
    EngineCore::EmitJSON($results);
}

function ModuleAction_main_tag_add($params)
{
    $evaid = intval($params[1]);
    $objtype=$params[0];
    $tag = EngineCore::POST("tag","");
    if(!$tag)
    {
        EngineCore::EmitJSON(['responseCode'=>"BadInput"]);
    }
    if(!EngineCore::CheckPermission("tag.super"))
    {
        
        EngineCore::EmitJSON(['responseCode'=>"Denied"]);
    }
    if(Tag::Attach($evaid,$tag,$objtype))
    {
        EngineCore::EmitJSON(['responseCode'=>"OK"]);
    }
    EngineCore::EmitJSON(['responseCode'=>"NoChange"]);
}