<?php
namespace Models\KB;
use Common\EditorJS\Document as EditorJSDocument;
use Common\DBHelper;
class Page
{
    
    
    
    
    
    public function __construct(
            private IPageDataProvider $PageProvider,
            private IGroupBacker $GroupProvider,
            public int $id, 
            public string $title, 
            public string $text,
            public string $html,
            public EditorJSDocument $ejsdoc,
            public int $project_id = 0,
            public int $latest_revision = 0,
            public int $created = 0,
            public int $modified = 0,
            public int $creator = 0){}
    
    public static function Load(IPageDataProvider $provider, IGroupBacker $groupDb, int $id)
    {
        $data = $provider->LoadPage($id);
        if($data===null)
        {
            return null;
        }
        $result = new Page(
                PageProvider: $provider,
                GroupProvider: $groupDb,
                id: $id, 
                title: $data->title,
                text: $data->text,
                ejsdoc: $data->ejsdoc,
                html: $data->html,
                project_id: $data->project_id,
                created: $data->created,
                latest_revision: $data->latest
        );
        return $result;
    }
    
    public function GetPageInfo() : PageInfo
    {
        return new PageInfo(
                title: $this->title,
                    project_id: $this->project_id,
                    ejsdoc: $this->ejsdoc,
                    text: $this->text,
                    html: $this->html,
                    latest: $this->latest_revision,
                    id: $this->id,
                    created:$this->created);
    }
    public function Save()
    {
        $page = $this->GetPageInfo();
        $this->PageProvider->SavePage($page);
    }
    public function SaveNewRevision()
    {
        $page = $this->GetPageInfo();
        $newLatest = $this->PageProvider->SaveRevision($page);
        $this->latest_revision=$newLatest->id;
    }
    public static function GetLastRevision(IPageDataProvider $provider, int $pageId) : PageRevision|null
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
            $newchapterNav = $this->ActionChapterNav($chapternav);
            if($newchapterNav['data']['index']==0)
            {
                $this->ejsdoc->RemoveChapterNav();
            }
            else
            {
                $this->ejsdoc->SetChapterNav($newchapterNav);
            }
        }
        $this->RenderHTML();
    }
    
    public function RenderHTML()
    {
        //no logical changes, only processes ejsdoc into html and plaintext
        $doc = self::ProcessIndexBlock(provider: $this->PageProvider, groupDb: $this->GroupProvider, doc: $this->ejsdoc, id: $this->id);
        $chapternav = $this->ejsdoc->GetChapterNav();
        if($chapternav)
        {
            $doc = self::ProcessChapterNav(provider: $this->PageProvider, groupDb: $this->GroupProvider, doc: $this->ejsdoc);
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
        $chapternav = self::MakeChapterNav($prev, $group, $next,$this->PageProvider,$this->GroupProvider);
        $this->ejsdoc->SetChapterNav($chapternav);
        $this->RenderHTML();
        $this->Save();
    }
    
    
    public static function MakeChapterNav($prev, $index, $next, IPageDataProvider $provider, IGroupBacker $groupDb, $full = false)
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
        $ppage = Page::Load(provider: $provider, groupDb: $groupDb, id: intval($prev));
        $npage = Page::Load(provider: $provider, groupDb: $groupDb, id: intval($next));
        $ipage = Page::Load(provider: $provider, groupDb: $groupDb, id: intval($index));
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
    
    public static function GenerateIndexBlock(IPageDataProvider $provider, IGroupBacker $groupDb, int $id)
    {
        $cat=PageGroup::Load(id:$id, backer: $groupDb);
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
            $page = Page::Load(provider: $provider, groupDb: $groupDb, id: $cat->items[$i]['id']);
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
    
    public static function ProcessIndexBlock(IPageDataProvider $provider, IGroupBacker $groupDb,$doc,$id)
    {
        $indexblock = self::GenerateIndexBlock($provider, $groupDb, $id);
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
    
    public static function ProcessChapterNav(IPageDataProvider $provider, IGroupBacker $groupDb,$doc)
    {
        $chapternav = $doc->GetChapterNav();
        $prev = $chapternav['data']['prev'] ?? -1;
        $index = $chapternav['data']['index'] ?? -1;
        $next = $chapternav['data']['next'] ?? -1;
        $topnav = self::MakeChapterNav($prev,$index,$next,$provider, $groupDb, true);
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
        $db = $this->GroupProvider;
        $cg = PageGroup::Find(backer: $db, id: $this->id);
        $cn = 0;
        $cp = 0;
        $np = intval($chapternav['data']['prev']);
        $ng = intval($chapternav['data']['index']);
        $nn =intval($chapternav['data']['next']);
        if($cg>0)
        {
            $currentGroup = PageGroup::Load(backer: $db, id: $cg);
            $iOf = $currentGroup->IndexOf($this->id);
            $itemdata= $currentGroup->items[$iOf];
            $cp = $itemdata['prev'];
            $cn = $itemdata['next'];
        }
        
        $newPos = PageGroup::ProcessMove(backer: $db,
                cg: $cg,
                cn: $cn,
                cp: $cp,
                ng: $ng,
                nn: $nn,
                np: $np,
                itemId: $this->id);
        
        if($newPos->noChange)
        {
            return $chapternav;
        }
        
        $updatednav = self::MakeChapterNav(index: $newPos->joinedGroup, prev: $newPos->previousItem, next: $newPos->nextItem, provider: $this->PageProvider, groupDb: $this->GroupProvider);
        //return $updatednav;
        $c = count($newPos->affectedItems);
        
        for($i=0;$i<$c;$i++)
        {
            $item = $newPos->affectedItems[$i];
            if($item['id']==$this->id)
            {
                continue;
            }
            
            $p = Page::Load(provider: $this->PageProvider, groupDb: $db, id: $item['id']);
            $gid = PageGroup::Find(backer: $db, id: $item['id']);
            $p->UpdateChapterNav(group: $gid, prev: $item['prev'], next: $item['next']);
        }
        
        if($newPos->joinedGroup>0)
        {
            //redo index
            $p = Page::Load(provider: $this->PageProvider, groupDb: $db, id: $newPos->joinedGroup);
            $p->RenderHTML();
            $p->Save();
        }
        if($newPos->leftGroup>0 && $newPos->leftGroup!=$newPos->joinedGroup)
        {
            // redo this one too   
            $p = Page::Load(provider: $this->PageProvider, groupDb: $db, id: $newPos->leftGroup);
            $p->RenderHTML();
            $p->Save();
        }
        return $updatednav;
        /*/
        $updates = PageSequence::ProcessMove($this->id,$chapternav['data']['index'],$chapternav['data']['prev'],$chapternav['data']['next']);
        $group = PageSequence::Load($updates[0]);
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
                $p = Page::Load($page['entityId']);
                $p->UpdateChapterNav($group->id, $page['prev'], $page['next']);
            }
            
            $groupPage = Page::Load($chapternav['data']['index']);
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
                    $groupPage = Page::Load($update['group']);
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

                $page = Page::Load($update['id']);
                if(!$page)
                {
                    continue;
                }
                $page->UpdateChapterNav($update['group'],$update['prev'],$update['next']);
            }    
        }
        return $updatednav;
        //*/
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
    /*/
    public static function generateIndex($id)
    {
        $output="";
        $db = new PageGroupDBBacker(tablename: 'kb_groups');
        $cat=PageGroup::Load(backer: $db, id: $id);
        $li ="\n\t<li><a href=\"/kb/view/%s\">%s</a></li>";
        $list_acc="";
        if($cat)
        {
            
            for($i=0;$i<count($cat->items);$i++)
            {
                $page = Page::Load($cat->items[$i]['id']);
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
    //*/
}

