<?php

function ModuleAction_main_tag_suggest($params)
{
    $evatype = count($params) > 1 ? $params[0] : "";
    $prefix = count($params) > 1 ? $params[1] : $params [0];
    $suggestions = Tag::GetSuggestions($prefix, $evatype);
    EngineCore::RawModeOn();
    HTTPHeaders::ContentType("application/json");
    echo "[\"" . implode("\", \"", $suggestions) . "\"]";
}
function ModuleAction_main_tag_find($params)
{
    
    $evatype = count($params) > 1 ? $params[0] : "";
    $tag = count($params) > 1 ? $params[1] : $params [0];
    $results = Tag::Find($evatype,$tag);
    EngineCore::RawModeOn();
    HTTPHeaders::ContentType("application/json");
    echo "[\"" . implode("\", \"", $results) . "\"]";
    
}

function ModuleAction_main_tag_get($params)
{
    $evaid = $params[0];
    $results = Tag::GetTags($evaid);
    EngineCore::RawModeOn();
    HTTPHeaders::ContentType("application/json");
    echo "[\"" . implode("\", \"", $results) . "\"]";
}

function ModuleAction_main_tag_add($params)
{
    $evaid = $params[0];
    $tag = EngineCore::POST("tag","");
    EngineCore::RawModeOn();
    HTTPHeaders::ContentType("application/json");
    if(!$tag)
    {
        echo '{"responseCode": "BadInput"}';
        die();
    }
    $obj = new EVA($evaid);
    if($obj->id < 1)
    {
        echo '{"responseCode": "NotFound"}';
        die();
    }
    if(!EngineCore::CheckPermission("tag.super"))
    {
        echo '{"responseCode": "Denied"}';
        die();
    }
    if(Tag::Attach($evaid,$tag))
    {
        echo '{"responseCode": "OK"}';
        die();
    }
        echo '{"responseCode": "NoChange"}';
    die();
}