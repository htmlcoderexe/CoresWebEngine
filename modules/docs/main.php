<?php

function ModuleAction_docs_new($params)
{
    if(Utility::POST("up")!="yes")
    {
        Utility::SetPageContent((new TemplateProcessor("docs/upload"))->process(true));
    }
    else
    {
        $file=File::Upload($_FILES['fileup']);
        if($file)
        {
            $title=Utility::POST("title","<Untitled>");
            $desc = Utility::POST("description",$title);
            $sensitivity= Utility::POST("sensitivity","0");
            $owner=Utility::POST("noshare","")===""?EVA::OWNER_NOBODY : EVA::OWNER_CURRENT;
            $doc = Document::Create($title, [$file->blobid],$desc,$owner,$sensitivity);
            Utility::GTFO("/docs/view/".$doc->id);
        }
        else
        {
            
        }
    }
}
function ModuleAction_docs_view($params)
{
    $docid=$params[0];
    $doc = new Document($docid);
    if($doc)
    {
        $docview=new TemplateProcessor("docs/viewdoc");
        $link= new TemplateProcessor("docs/filelink");
        $links="";
        $fileIDs=is_array($doc->fileIDs)?$doc->fileIDs : [$doc->fileIDs];
        foreach($fileIDs as $fileid)
        {
            $file = File::GetByBlobID($fileid);
            $link->tokens['blobid']=$file->blobid;
            $link->tokens['filename']=$file->fname;
            $link->tokens['size']=Utility::hfilesize($file->filesize);
            $links.=$link->process(true);
        }
        $docview->tokens['title']=$doc->title;
        $docview->tokens['description']=$doc->description;
        $docview->tokens['files']=$links;
        Utility::SetPageContent($docview->process(true));
    }
}

function ModuleAction_docs_upload($params)
{
    
}