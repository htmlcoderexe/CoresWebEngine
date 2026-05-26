<?php

function ModuleAction_kb_api_head($params)
{
    $id = $params[0] ?? -1;
    $provider = new KBPageDataProviderDB(pageTable: 'kb_pages', revisionTable: 'kb_page_revisions');
    $gdb = new KBGroupDBBacker(tablename: 'kb_groups');
    $page = KBPage::Load(provider: $provider, groupDb: $gdb, id: $id);
    $data = null;
    if($page)
    {
       $data = [
           'title'=>$page->title,
           'id'=>$page->id,
           'excerpt'=>substr($page->text,0,150),
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

function ModuleAction_kb_api_suggest($params)
{
    $prefix = $params[0] ?? "";
    $data = [];
    if($prefix != "")
    {
    
        $query_params = ["%" . $prefix . "%"];
        $q = "SELECT title,id FROM kb_pages WHERE title LIKE ?";
        $data = DBHelper::RunTable($q,$query_params);
    }
    HTTPHeaders::Status(200);
    EngineCore::EmitJSON($data);
}