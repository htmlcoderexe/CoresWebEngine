<?php

function ModuleAction_kb_api_head($params)
{
    $pageId = $params[0] ?? -1;
    $page = KB_Page::Load($pageId);
    $data = null;
    if($page)
    {
       $data = [
           'title'=>$page->title,
           'id'=>$page->id,
           'excerpt'=>substr($page->raw,0,150),
           'isIndex'=>false
       ];
       if(KBPageSequence::Exists($page->id))
       {
           $data['isIndex'] = true;
       }
       HTTPHeaders::Status(200);
       EngineCore::EmitJSON($data);
       
    }
    HTTPHeaders::Status(404);
    EngineCore::EmitJSON($data);
}