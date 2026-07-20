<?php

function ModuleAction_docs_new($params)
{
    if(EngineCore::POST("up")!="yes")
    {
        EngineCore::SetPageContent((new TemplateProcessor("docs/upload"))->process(true));
    }
    else
    {
        $file=File::Upload($_FILES['fileup']);
        if($file)
        {
            $title=EngineCore::POST("title","<Untitled>");
            $desc = EngineCore::POST("description",$title);
            $sensitivity= EngineCore::POST("sensitivity","0");
            $doctype = intval(EngineCore::POST("doctype",0));
            $owner=EngineCore::$CurrentUser->userid;
            $doc = Document::Create(title: $title, filelist: [$file->blobid],description:$desc,owner:$owner,visibility:$sensitivity, doctype: $doctype);
            EngineCore::GTFO("/docs/view/".$doc->id);
        }
        else
        {
            
        }
    }
}

function ModuleAction_docs_view($params)
{
    $docid=intval($params[0]);
    $doc = Document::Load($docid);
    if($doc)
    {
        $docview=new TemplateProcessor("docs/viewdoc");
        $tags = Tag::GetTags($doc->id,"document");
        $docview->tokens['tags'] = $tags;
        $link= new TemplateProcessor("docs/filelink");
        $links="";
        foreach($doc->files as $fileobj)
        {
            $fileid = $fileobj->blobid;
            $file = File::Load($fileid);
            $link->tokens['blobid']=$file->blobid;
            $link->tokens['filename']=$file->fname;
            $link->tokens['size']=Utility::FormatUnit($file->size);
            $link->tokens['ext']=$file->filext;
            $links.=$link->process(true);
        }
        $docview->tokens['title']=$doc->title;
        $docview->tokens['description']=$doc->description;
        $docview->tokens['files']=$links;
        $docview->tokens['id']=$doc->id;
        EngineCore::SetPageContent($docview->process(true));
    }
}

function ModuleAction_docs_list($params)
{
    $cats = [
        'other'=>0,
        'books'=>1,
        'manuals'=>2,
        'whitepapers'=>3,
        'events'=>4,
        'admin'=>5,
        'receipts'=>6,
        'certs'=>7,
        'references'=>8
    ];
    $uid = intval(EngineCore::$CurrentUser->userid);
    $tpl = new TemplateProcessor('docs/doclistview');
    $docs = null;
    if($params && count($params)>0)
    {
        $cat = $params[0];
        if(isset($cats[$cat]))
        {
            $tpl->tokens['current'] = $cat;
            $docs = Document::GetAll(userid: $uid, type: $cats[$cat]);
            $tpl->tokens['shownav']='true';
        }
    }
    if($docs===null)
    {
        $docs = Document::GetAll(userid: $uid);
    }
    $tpl->tokens['docs']=$docs;
    EngineCore::SetPageContent($tpl->process(true));
}

function ModuleAction_docs_tag($params)
{
    // for now just the one lol
    $tag=$params[0];
    $results = Tag::Find("document",$tag);
    $q=DBHelper::Select(table: Document::TABLE, fields: Document::FIELDS, where: []) . " WHERE id IN (?". str_repeat(",?", count($results)-1) . ") AND (visibility = 0 OR uid = ? )";
    $results[]= EngineCore::$CurrentUser->userid;
    $docsrows = DBHelper::RunTable($q,$results);
    $docs = [];
    foreach($docsrows as $row)
    {
        // no need to load filelists
        $doc = Document::FromRow($row, []);
        $docs[]=$doc;
    }
    $tpl = new TemplateProcessor('docs/doclistview');
    $tpl->tokens['docs']=$docs;
    $tagtpl=new TemplateProcessor("system/showtags");
    $tagtpl->tokens['tags']=[$tag];
    $tagtpl->tokens['boxid']="tag_box_search";
    EngineCore::AddPageContent($tagtpl->process(true));
    EngineCore::AddPageContent($tpl->process(true));
    return;
    
}

function ModuleAction_docs_upload($params)
{
    
}




function ModuleAction_docs_migrate($params)
{
    $user=User::GetCurrentUser();
    if(!$user->HasPermission("super"))
    {
        EngineCore::FromWhenceYouCame();
        die;
    }
    $propertylist = [
        "title",
        "description",
        "blobid",
        "document.sensitivity"
    ];
    $docs = EVA::GetAsTable($propertylist, "document");
    foreach($docs as $id=>$doc)
    {
        $newdoc = Document::Create(
                title: $doc['title'], 
                description: $doc['description'], 
                visibility: intval($doc['document.sensitivity']), 
                filelist: [$doc['blobid']]
                );
        var_dump($newdoc);
    }
    die;
}


//*/
