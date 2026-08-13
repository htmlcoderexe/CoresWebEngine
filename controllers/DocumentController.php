<?php

namespace Controllers;
use \Cores\EngineCore as EngineCore;
use \Route as Route;
use \Common\DBHelper as DBHelper;

use \Models\Documents\Document as Document;
use \Models\File as File;
use \Models\Tags\Tag as Tag;

/**
 * Description of DocumentViewer
 *
 * @author admin
 */

class DocumentController
{
    #[Route('docs/view')]
    public static function View($id=0)
    {
        $entity_type = "docs/viewdoc";
        $docid=intval($id);
        $doc = Document::Load($docid);
        $entity = [];
        if($doc)
        {
            $entity = (array)$doc;
            //$docview=new \TemplateProcessor("docs/viewdoc");
            $entity['tags'] = \Tag::GetTags($doc->id,"document");
            //$docview->tokens['tags'] = $tags;
            //$link= new \TemplateProcessor("docs/filelink");
            $links="";
            if($doc->thumbnail!="")
            {
                $thumb = File::Load($doc->thumbnail);
                if($thumb)
                {
                    $entity['thumbnail'] = $thumb->blobid;
                    $entity['thumbnail_ext'] = $thumb->filext;
                }
            }
            $entity['files'] = [];
            foreach($doc->files as $fileobj)
            {
                $fileid = $fileobj->blobid;
                $file = File::Load($fileid);
                $entity['files'][]=$file;
            }
            $entity['entity_type']=$entity_type;
            return $entity;
        }
    }
    
    #[Route('docs/list')]
    public static function List($cat = '')
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
        $uid = intval(\EngineCore::$CurrentUser->userid);
        $entity_type = 'docs/doclistview';
        $docs = null;
        $entity = [];
        if($cat!='')
        {
            if(isset($cats[$cat]))
            {
                $entity['current'] = $cat;
                $docs = Document::GetAll(userid: $uid, type: $cats[$cat]);
                $entity['shownav']='true';
            }
        }
        if($docs===null)
        {
            $docs = Document::GetAll(userid: $uid);
        }
        $entity['docs']=$docs;
        
        $entity['entity_type'] = $entity_type;
        
        return $entity;
    }
    #[Route('docs/new')]
    public static function New()
    {
        if(!EngineCore::IsPOST())
        {
            return ['entity_type'=>'docs/upload'];
        }
        $def = [
            'title'=>'<Untitled>',
            'description'=>'',
            'sensitivity'=>0,
            'doctype'=>0];
        $data = EngineCore::GetSubmission($def);
        
        $file=File::Upload($_FILES['fileup']);
        if($file)
        {
            $title=$data["title"];
            $desc = $data["description"];
            $sensitivity= intval($data["sensitivity"]);
            $doctype = intval($data["doctype"]);
            $owner=EngineCore::$CurrentUser->userid;
            $doc = Document::Create(title: $title, filelist: [$file->blobid],description:$desc,owner:$owner,visibility:$sensitivity, doctype: $doctype);
            EngineCore::GTFO("/docs/view/".$doc->id);
        }
        else
        {
            return ['entity_type'=>'docs/upload'];
        }
        
    }
    
    #[Route('docs/tag')]
    public static function FindByTag($tag = '')
    {    // for now just the one lol
        $results = Tag::Find("document",$tag);
        $docs = [];
        if($results)
        {
            $q=DBHelper::Select(table: Document::TABLE, fields: Document::FIELDS, where: []) . " WHERE id IN (?". str_repeat(",?", count($results)-1) . ") AND (visibility = 0 OR uid = ? )";
            $results[]= EngineCore::$CurrentUser->userid;
            $docsrows = DBHelper::RunTable($q,$results);
            foreach($docsrows as $row)
            {
                // no need to load filelists
                $doc = Document::FromRow($row, []);
                $docs[]=$doc;
            }

        }
        $entity = [];
        $entity['docs']=$docs;
        
        $entity['entity_type'] = 'docs/doclist';
        $entity['tags']=[$tag];
        return $entity;
    }
}
