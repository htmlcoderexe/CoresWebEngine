<?php
require_once "KBPageDataProvider.php";

class KBPage
{
    
    public KBPageDataProvider $DataProvider;
    public $text;
    public $html;
    public $ejsdoc;
    
    public $latest_revision;
    public $title;
    public $id;
    public $project_id;
    
    public $created;
    public $modified;
    public $creator;
    
    
    public function __construct($provider, $id, $title, $ejsdoc, $text, $html, $project_id = 0)
    {
        $this->DataProvider = $provider;
        $this->title=$title;
        $this->text=$text;
        $this->ejsdoc=$ejsdoc;
        $this->id=$id;
        $this->html=$html;
        $this->project_id=$project_id;
    }
    public static function Load(KBPageDataProvider $provider, int $id)
    {
        $data = $provider->LoadPage($id);
        if($data===null)
        {
            return null;
        }
        $result = new KBPage(
                provider: $provider, 
                id: $id, 
                title: $data->title,
                text: $data->text,
                ejsdoc: $data->ejsdoc,
                html: $data->html,
                project_id: $data->project_id
        );
        $result->created = $data->created;
        $result->latest = $data->latest;
        return $result;
    }
    
    public function GetKBPageInfo() : KBPageInfo
    {
        return new KBPageInfo(
                title: $this->title,
                    project_id: $this->project_id,
                    ejsdoc: json_encode($this->ejsdoc),
                    text: $this->text,
                    html: $this->html,
                    latest: $this->latest_revision,
                    id: $this->id);
    }
    public function Save()
    {
        $page = $this->GetKBPageInfo();
        $this->DataProvider->SavePage($page);
    }
    public function SaveNewRevision()
    {
        $page = $this->GetKBPageInfo();
        $newLatest = $this->DataProvider->SaveRevision($page);
        $this->latest_revision=$newLatest->id;
    }
    public static function GetLastRevision(KBPageDataProvider $provider, int $pageId) : KBPageRevision
    {
        $revId = $provider->GetLatestRevisionID($pageId);
        $rev = $provider->LoadRevision($revId);
        return $rev;
    }
    
    public function ProcessPage()
    {
        // when actual source changed 
        $chapternav = $this->ejsdoc->GetChapterNav();
        // update index links if necessary
        if($chapternav)
        {
            $this->ActionChapterNav($chapternav);
        }
        $this->RenderHTML();
    }
    
    public function RenderHTML()
    {
        //no logical changes, only processes ejsdoc into html and plaintext
        $doc = self::ProcessIndexBlock($this->ejsdoc,$this->id);
        $chapternav = $this->ejsdoc->GetChapterNav();
        if($chapternav)
        {
            $doc = self::ProcessChapterNav($this->ejsdoc);
        }
        
        $this->text = $this->ejsdoc->GetPlainText();
        $this->html = $doc->GetHTML();
    }
    
    
    
    public function SetDoc($doc)
    {
        $this->ejsdoc = $doc;
        
    }
    public function UpdateChapterNav($group,$prev,$next)
    {
        $chapternav = self::MakeChapterNav($prev, $group, $next);
        $this->ejsdoc->SetChapterNav($chapternav);
        $this->RenderHTML();
        $this->Save();
    }
    
    
    public static function MakeChapterNav($prev, $index, $next,$full = false)
    {
        if(!$full)
        {
            $chapternav = [
                'type'=>'chapternav',
                'data'=>[
                    'modified'=> false,
                    'prev'=>$prev,
                    'index'=>$index,
                    'next'=>$next
                ]
            ];
            return $chapternav;
        }
        $processednav = [
            'type'=>'chapternav',
            'data'=>[]
        ];
        $ppage = KBPage::Load($prev);
        $npage = KBPage::Load($next);
        $ipage = KBPage::Load($index);
        if($ppage)
        {
            $processednav['data']['prev'] = $prev;
            $processednav['data']['prevtitle'] = $ppage->title;
        }
        if($npage)
        {
            $processednav['data']['next'] = $next;
            $processednav['data']['nexttitle'] = $npage->title;
        }
        if($ipage)
        {
            $processednav['data']['index'] = $index;
            $processednav['data']['indextitle'] = $ipage->title;
        }
        return $processednav;
    }
    
    public static function GenerateIndexBlock($id)
    {
        $cat=KBGroup::Load($id);
        $li ="<a href=\"/kb/view/%s\">%s</a>";
        if(!$cat)
        {
            return null;
        }    
        $block = [
            'type'=>'list',
            'data'=> [
                'style'=>'unordered',
                'items'=>[]
            ]
        ];
        $items = false;
        for($i=0;$i<count($cat->items);$i++)
        {
            $page = KBPage::Load($cat->items[$i]['id']);
            if($page)
            {
                $block['data']['items'][]=['content'=>sprintf($li,$page->id,$page->title)];
                $items = true;
            }
        }
        if($items)
        {
            return $block;
        }
        
        return null;
        
    }
    
    public static function ProcessIndexBlock($doc,$id)
    {
        $indexblock = self::GenerateIndexBlock($id);
        $newblocks = [];
        $indexblockplaced = false;
        foreach($doc->blocks as $block)
        {
            if($block['type']=='chapterindex')
            {
                if(!$indexblockplaced && $indexblock)
                {
                    $newblocks[]=$indexblock;
                    $indexblockplaced = true;
                }
            }
            else
            {
                $newblocks[]=$block;
            }
        }
        if(!$indexblockplaced && $indexblock)
        {
            $newblocks[]=$indexblock;
        }
        return EditorJSDocument::FromBlocks($newblocks);
    }
    
    public static function ProcessChapterNav($doc)
    {
        $chapternav = $doc->GetChapterNav();
        $prev = $chapternav['data']['prev'] ?? -1;
        $index = $chapternav['data']['index'] ?? -1;
        $next = $chapternav['data']['next'] ?? -1;
        $topnav = self::MakeChapterNav($prev,$index,$next,true);
        $bottomnav = $topnav;
        $bottomnav['data']['bottom']=true;
        $newblocks = [$topnav];
        foreach($doc->blocks as $block)
        {
            if($block['type']!='chapternav')
            {
                $newblocks[]=$block;
            }
        }
        $newblocks[]=$bottomnav;
        return EditorJSDocument::FromBlocks($newblocks);
        
    }
    
    public function ActionChapterNav($chapternav)
    {
        $updatednav = $chapternav;
        $db = new KBGroupDBBacker(tablename: "kb_groups");
        $cg = KBGroup::Find(backer: $db, id: $this->id);
        $cn = 0;
        $cp = 0;
        $np = $chapternav['data']['prev'];
        $ng = $chapternav['data']['index'];
        $nn =$chapternav['data']['next'];
        if($cg>0)
        {
            $currentGroup = KBGroup::Load(backer: $db, id: $cg);
            $iOf = $currentGroup->IndexOf($this->id);
            $itemdata= $currentGroup->items[$iOf];
            $cp = $itemdata['prev'];
            $cn = $itemdata['next'];
        }
        
        $newPos = KBGroup::ProcessMove(backer: $db,
                cg: $cg,
                cn: $cn,
                cp: $cp,
                ng: $ng,
                nn: $nn,
                np: $np,
                itemId: $this->id);
        
        if($newPos['left'] ==0 && $newPos['joined'] ==0)
        {
            return $chapternav;
        }
        
        
        $updates = KBPageSequence::ProcessMove($this->id,$chapternav['data']['index'],$chapternav['data']['prev'],$chapternav['data']['next']);
        $group = KBPageSequence::Load($updates[0]);
        if($group)
        {
            foreach($group->pages->items as $page)
            {
                if($page['entityId']==$this->id)
                {
                    
                    $updatednav = self::MakeChapterNav($page['prev'],$group->id,$page['next']);
                    $this->ejsdoc->SetChapterNav($updatednav);
                    continue;
                }
                $p = KBPage::Load($page['entityId']);
                $p->UpdateChapterNav($group->id, $page['prev'], $page['next']);
            }
            
            $groupPage = KBPage::Load($chapternav['data']['index']);
            if($groupPage)
            {
                Logger::log("updated group page");
                $groupPage->RenderHTML();
                $groupPage->Save();
            }
        }
        return $updatednav;
        
        
        $updatednav = $chapternav;
        if(count($updates)>1)
        {
            $gid=-1;
            foreach($updates as $update)
            {
                if($update['group']!=$gid)
                {
                    $groupPage = KBPage::Load($update['group']);
                    if($groupPage)
                    {
                        Logger::log("updated group page");
                        $groupPage->RenderHTML();
                        $groupPage->Save();
                    }
                    $gid=$update['group'];
                }
                if($update['id']==$this->id)
                {
                    $updatednav = self::MakeChapterNav($update['prev'],$update['group'],$update['next']);
                    $this->ejsdoc->SetChapterNav($updatednav);
                    continue;
                }

                $page = KBPage::Load($update['id']);
                if(!$page)
                {
                    continue;
                }
                $page->UpdateChapterNav($update['group'],$update['prev'],$update['next']);
            }    
        }
        return $updatednav;
    }
    
    
    //////////////////////////////////////////////////////////////////////
    //////// legacy stuff remove once not needed
    
    
    
    public static function GetSpecificRevision($id)
    {
        //note, this does not check if any revision belongs to the page yet.
        $q=DBHelper::Select("kb_page_revisions",['id','content_json','content_raw','content_html'],['id'=>$id],['timestamp'=>'DESC']);
        $rev=DBHelper::RunRow($q,[$id]);
        return $rev['content_html'];
    }
    
    public static function generateIndex($id)
    {
        $output="";
        $db = new KBGroupDBBacker(tablename: 'kb_groups');
        $cat=KBGroup::Load(backer: $db, id: $id);
        $li ="\n\t<li><a href=\"/kb/view/%s\">%s</a></li>";
        $list_acc="";
        if($cat)
        {
            
            for($i=0;$i<count($cat->items);$i++)
            {
                $page = KBPage::Load($cat->items[$i]['id']);
                if($page)
                {
                    $list_acc.=sprintf($li,$page->id,$page->title);
                }
            }
            if($list_acc!="")
            {
                $output="\n<ul class=\"articleindex\">".$list_acc.'</ul>';
            }
        }
        return $output;
    }
}