<?php

/**
 * Description of KBPageDataProviderDB
 *
 */
class KBPageDataProviderDB implements IKBPageDataProvider
{
    
    public function __construct(
            public string $pageTable, 
            public string $revisionTable){}
    
    public function LoadPage(int $id) : KBPageInfo|null
    {
        $fields = ["id,title,created,project_id,modified,creator_id,latest,html,text,ejsdoc"];
        $q = DBHelper::Select($this->pageTable,$fields,["id"=>$id]);
        $page = DBHelper::RunRow($q,[$id]);
        if(!$page)
        {
            return null;
        }
        $doc = EditorJSDocument::FromJSON($page['ejsdoc']);
        if(!$doc)
        {
            $doc = new EditorJSDocument();
        }
        $result = new KBPageInfo(
                id: $id,
                title: $page['title'],
                
                text: $page['text'],
                html: $page['html'],
                ejsdoc: $doc,
                
                project_id: $page['project_id'],
                created: $page['created']
        );
        return $result;
    }
    
    public function SavePage(KBPageInfo $page)
    {
        DBHelper::Update($this->pageTable,
                ['title'=>$page->title,
                    'project_id'=>$page->project_id,
                    'ejsdoc'=>json_encode($page->ejsdoc),
                    'text'=>$page->text,
                    'html'=>$page->html,
                    'latest'=>$page->latest],
                ['id'=>$page->id]);
    }

    public function SaveRevision(\KBPageInfo $page) : KBPageRevision
    {
        $time =time();
        $json = json_encode($page->ejsdoc);
        $d=[null,$page->id,$page->title,$json,$page->text,$page->html,$time,0];
        DBHelper::Insert($this->revisionTable,$d);
        $latest = DBHelper::GetLastId();
        return new KBPageRevision(
                id: $latest,
                title: $page->title,
                json: $json,
                text: $page->text,
                html: $page->html,
                timestamp: $time,
                userId: 0,
                pageId: $page->id);
    }

    public function GetLatestRevisionID(int $pageId): int
    {
        $q = DBHelper::Select(
                table: $this->revisionTable,
                fields: ['id'],
                where: ['page_id'=>$pageId],
                orderby:['timestamp'=>'DESC']);
        $result = DBHelper::RunScalar($q, [$pageId]);
        if($result === false)
        {
            return 0;
        }
        return $result;
    }

    public function LoadRevision(int $revisionId): KBPageRevision
    {
        $q = DBHelper::Select($this->revisionTable, ['id','content_json','content_plaintext','content_html','page_id','timestamp','userid', 'title'],['id'=>$revisionId]);
        $results = DBHelper::RunRow($q,[$revisionId]);
        if($results === false)
        {
            return null;
        }
        return new KBPageRevision(
                id: $results['id'],
                title: $results['title'],
                json: $results['content_json'],
                text: $results['content_plaintext'],
                html: $results['content_html'],
                timestamp: $results['timestamp'],
                userId: $results['userid'],
                pageId: $results['page_id']);
    }
}
