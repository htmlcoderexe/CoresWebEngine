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
            $owner=EngineCore::POST("noshare","")===""?EVA::OWNER_NOBODY : EVA::OWNER_CURRENT;
            $doc = Document::Create(title: $title, filelist: [$file->blobid],description:$desc,owner:$owner,visibility:$sensitivity);
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
        EngineCore::SetPageContent($docview->process(true));
    }
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
