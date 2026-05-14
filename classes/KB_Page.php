<?php
class KB_Page
{
    public $raw;
    private $processed;
    
    public $last_revision;
    public $title;
    public $id;
    public $project_id;
    
    public $created;
    public $modified;
    public $creator;
    
    public $ejsdoc;
    
    public function __construct($id,$title,$content_ejsdoc,$content_plaintext,$content_html,$project_id = 0)
    {

        $this->title=$title;
        $this->raw=$content_plaintext;
        $this->ejsdoc=$content_ejsdoc;
        $this->id=$id;
        $this->processed=$content_html;
        $this->project_id=$project_id;
    }
    public static function Load($id)
    {
        $fields = ["id,title,created,project_id,modified,creator_id"];
        $q = DBHelper::Select("kb_pages",$fields,["id"=>$id]);
        $page = DBHelper::RunRow($q,[$id]);
        if(!$page)
        {
            return null;
        }
        $raw="";
        $html="";
        $doc = null;
        $content = KB_Page::GetLastRevision($id);
        if($content)
        {
            $raw=$content["content_raw"];
            $doc = EditorJSDocument::FromJSON($content["content_json"]);
            $html=$content["content_html"];
        }
        $result = new KB_Page($id,$page['title'],$doc,$raw,$html,$page['project_id']);
        $result->created =$page['created'];
        
        return $result;
    }
    public function GetAffectedPages()
    {
        $id=$this->id;
        $stmt = DBHelper::$DBLink->prepare("SELECT id,page_updated,page_affected FROM kb_page_dependencies WHERE page_updated=?");
        $stmt->bindParam(1,$id);
        $q = DBHelper::Select("kb_page_dependencies",['id','page_updated','page_affected'], ['page_updated'=>$id]);
        $pages=DBHelper::RunTable($q,[$id]);
        return $pages;
    }
    public function CheckUpdate()
    {

    }
    public function Save()
    {
        DBHelper::Update("kb_pages",['title'=>$this->title,'project_id'=>$this->project_id],['id'=>$this->id]);
        $d=[null,$this->id,json_encode($this->ejsdoc),$this->raw,$this->processed,time(),0];
        DBHelper::Insert('kb_page_revisions',$d);
    }
    public static function  SaveToDatabase($id,$text)
    {
        $processed=KB_Page::ProcessMarkup($id, $text);
        $d=Array(null,$id,$text,$processed,time(),0);
        DBHelper::Insert('kb_page_revisions',$d);
        return $processed;
    }
    public static function CreatePage($title,$projId=-1)
    {
        if($projId==-1)
        {
            $projId=KB::CurrentProjectID();
        }
        DBHelper::$DBLink->beginTransaction();
        DBHelper::Insert('kb_pages',Array(null,$title,time(),$projId,time(),1));
        //Utility::Debug(mysql_error());
        $id=DBHelper::GetLastId();
        DBHelper::$DBLink->commit();
        return $id;
    }
    
    public function updatesomefuckingshit()
    {
        $output = $this->raw;
        $matches = KB_Page::GetLinkMarkupMatches($output);
        $grouptags = KB_Page::doGroupLinks($output, $matches);
        $updates = KBPageSequence::ProcessMove($this->id,$grouptags['group'],$grouptags['prev'],$grouptags['next']);
        //var_dump($grouptags);
        if(count($updates)<1)
        {
            return;
        }
        //var_dump($updates);
        $gid=-1;
        foreach($updates as $update)
        {
            if($update['id']==$this->id)
            {
                $this->raw=KB_Page::updateGroupLinks($grouptags,$update,$output);
                continue;
            }
            
            $page = KB_Page::Load($update['id']);
            if(!$page)
            {
                continue;
            }
            $page->UpdateRefs($update['group'],$update['prev'],$update['next']);
            if($update['group']!=$gid)
            {
                $groupPage = KB_Page::Load($update['group']);
                if($groupPage)
                {
                    $groupPage->SetBody($groupPage->raw);
                    $groupPage->Save();
                }
                $gid=$update['group'];
            }
        }
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
        $this->ProcessDoc();
    }
    
    public function ProcessDoc()
    {
        //no logical changes, only processes ejsdoc into html and plaintext
        $chapternav = $this->ejsdoc->GetChapterNav();
        $doc = $this->ejsdoc;
        if($chapternav)
        {
            $doc = $this->ProcessChapterNav();
        }
        $this->raw = $this->ejsdoc->GetPlainText();
        $this->processed = $doc->GetHTML();
    }
    
    public function ProcessHTML($passive=false)
    {
        /*
         * The rest of the fucking owl...
         * 
         */
        if(!$passive)
        {
            $this->updatesomefuckingshit();
        }
        
        $output = $this->raw;
        $matches = KB_Page::GetLinkMarkupMatches($output);
        $output=KB_Page::doSpecialLinks($output,$matches);
        $matches = array_filter($matches);
        $output=KB_Page::process_links($output,$matches);
        $output.=self::generateIndex($this->id);
        $this->processed = $output;
        //die;
    
    }
    public function GetRaw()
    {
        return $this->raw;
    }
    
    
    public function SetBody($body)
    {
        $this->raw=$body;
        $this->ProcessHTML();
    }
    
    public function SetDoc($doc)
    {
        $this->ejsdoc = $doc;
        
    }
    public function UpdateRefs($group,$prev,$next)
    {
        $matches = KB_Page::GetLinkMarkupMatches($this->raw);
        $specials = KB_Page::doGroupLinks($this->raw,$matches);
        $this->raw = KB_Page::updateGroupLinks($specials,['prev'=>$prev,'next'=>$next,'group'=>$group],$this->raw);
        $this->processed=KB_Page::ProcessMarkup($this->id,$this->raw,true);
        $this->Save();
    }
    public function UpdateRefsNew($group,$prev,$next)
    {
        $chapternav = self::MakeChapterNav($prev, $group, $next);
        $this->ejsdoc->SetChapterNav($chapternav);
        $this->ProcessDoc();
        $this->Save();
    }
    public function GetHTML()
    {
        return $this->processed;
    }
    public static function GetLastRevision($id)
    {
        //$stmt = DBHelper::$DBLink->prepare("SELECT id,content_raw,content_html FROM kb_page_revisions WHERE page_id=? ORDER BY timestamp DESC");
        //$stmt->bindParam(1, $id);
        //$rev=DBHelper::GetOneRow($stmt);
        $rev=DBHelper::RunRow("SELECT id,content_json,content_raw,content_html FROM kb_page_revisions WHERE page_id=? ORDER BY timestamp DESC",[$id]);
        return $rev;
    }
    public static function GetSpecificRevision($id)
    {
        //note, this does not check if any revision belongs to the page yet.
        $q=DBHelper::Select("kb_page_revisions",['id','content_json','content_raw','content_html'],['id'=>$id],['timestamp'=>'DESC']);
        $rev=DBHelper::RunRow($q,[$id]);
        return $rev['content_html'];
    }
    
    static function doGroupLinks($input, $matches)
    {
        $output = $input;
        $nextId=null;
        $prevId=null;
        $groupId=null;
        $tags = ['prev'=>null,'next'=>null,'group'=>null];
        for($i=0;$i<count($matches);$i++)
        {
            list($pageId,$linkText,$fullmatch)=$matches[$i];
            if($linkText[0]=="#")
            {
                $cmd = substr($linkText,1);
                switch($cmd)
                {
                    case "next":
                    {
                        $tags['next'] = $pageId;
                        $tags['nextmatch']=$fullmatch;
                        break;
                    }
                    case "prev":
                    {
                        $tags['prev'] = $pageId;
                        $tags['prevmatch']=$fullmatch;
                        break;
                    }
                    case "index":
                    {
                        $tags['group'] = $pageId;
                        $tags['groupmatch']=$fullmatch;
                        break;
                    }
                }
            }
            else
            {
                
            }
        }
        return $tags;
    }
    
    static function doSpecialLinks($input,&$matches)
    {
        $output = $input;
        $nextId=-1;
        $prevId=-1;
        $gId=-1;
        for($i=0;$i<count($matches);$i++)
        {
            list($pageId,$linkText,$fullmatch)=$matches[$i];
            if($linkText[0]=="#")
            {
                $cmd = substr($linkText,1);
                switch($cmd)
                {
                    case "next":
                    {
                        $nextId = $pageId;
                        break;
                    }
                    case "prev":
                    {
                        $prevId = $pageId;
                        break;
                    }
                    case "index":
                    {
                        $gId = $pageId;
                        break;
                    }
                }
                $output=str_replace($fullmatch,"",$output);
                $matches[$i]=null;
            }
            else
            {
                
            }
        }
        $prevlink="";
        $nextlink="";
        $indexlink="";
        $prevlinktpl = "<div class=\"prevlink\"><a href=\"/kb/view/%s\">&lt; %s</a></div>";
        $nextlinktpl = "<div class=\"nextlink\"><a href=\"/kb/view/%s\">%s &gt;</a></div>";
        $indexlinktpl = "<div class=\"indexlink\"><a href=\"/kb/view/%s\">%s</a></div>";
        $topnav="";
        $bottomnav="";
        if($prevId>0)
        {
            $prevpage=KB_Page::Load($prevId);
            if($prevpage)
            {
                $prevlink=sprintf($prevlinktpl,$prevId,$prevpage->title);
            }
        }
        if($nextId>0)
        {
            $nextpage=KB_Page::Load($nextId);
            if($nextpage)
            {
                $nextlink=sprintf($nextlinktpl,$nextId,$nextpage->title);
            }
        }
        if($gId>0)
        {
            $indexpage=KB_Page::Load($gId);
            if($indexpage)
            {
                $indexlink=sprintf($indexlinktpl,$gId,$indexpage->title);
            }
        }
        if($nextId>0 || $prevId>0)
        {
            $topnavtpl="<nav id=\"kb_top_nav\">%s%s%s</nav>\n";
            
            $bottomnavtpl="\n<nav id=\"kb_bottom_nav\">%s%s%s</nav>";
            
            $topnav=sprintf($topnavtpl,$prevlink,$indexlink,$nextlink);
            $bottomnav=sprintf($bottomnavtpl,$prevlink,$indexlink,$nextlink);
        }
        return $topnav.$output.$bottomnav;
    }
    public static function process_links($input,$matches)
    {
        for($i=0;$i<count($matches);$i++)
        {
            list($pageId,$linkText,$fullmatch)=$matches[$i];
            $tpl = "<a href=\"/kb/view/%s\">%s</a>";
            $input=str_replace($fullmatch,sprintf($tpl,$pageId,$linkText),$input);
        }
        return $input;
    }
    public static function updateGroupLinks($matches,$values,$input)
    {
        $output=$input;
        //var_dump([$matches],$values,$input);
        if($matches['prevmatch']??null)
        {
            $output = str_replace($matches['prevmatch'],"[[".$values['prev']."|#prev]]",$output);
            
        }
        elseif($values['prev']??null)
        {
            $output="[[".$values['prev']."|#prev]]".$output;
        }
        if($matches['groupmatch']??null)
        {
            $output = str_replace($matches['groupmatch'],"[[".$values['group']."|#index]]",$output);
        }
        elseif($values['group']??null)
        {
            $output="[[".$values['group']."|#index]]".$output;
        }
        if($matches['nextmatch']??null)
        {
            $output = str_replace($matches['nextmatch'],"[[".$values['next']."|#next]]",$output);
        }
        elseif($values['next']??null)
        {
            $output="[[".$values['next']."|#next]]".$output;
        }
        return $output;
    }    
    public static function generateIndex($id)
    {
        $output="";
        //_p("INDEX GENERATED");die;
        $cat=KBPageSequence::Load($id);
        $li ="\n\t<li><a href=\"/kb/view/%s\">%s</a></li>";
        $list_acc="";
        if($cat)
        {
            
            for($i=0;$i<count($cat->pages);$i++)
            {
                $page = KB_Page::Load($cat->pages->items[$i]['entityId']);
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
        $ppage = KB_PAGE::Load($prev);
        $npage = KB_PAGE::Load($next);
        $ipage = KB_PAGE::Load($index);
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
    
    public function ProcessChapterNav()
    {
        $chapternav = $this->ejsdoc->GetChapterNav();
        $prev = $chapternav['data']['prev'] ?? -1;
        $index = $chapternav['data']['index'] ?? -1;
        $next = $chapternav['data']['next'] ?? -1;
        $topnav = $this::MakeChapterNav($prev,$index,$next,true);
        $bottomnav = $topnav;
        $bottomnav['data']['bottom']=true;
        $newblocks = [$topnav];
        foreach($this->ejsdoc->blocks as $block)
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
        $updates = KBPageSequence::ProcessMove($this->id,$chapternav['data']['index'],$chapternav['data']['prev'],$chapternav['data']['next']);
        $updatednav = $chapternav;
        if(count($updates)>1)
        {
            $gid=-1;
            foreach($updates as $update)
            {
                if($update['id']==$this->id)
                {
                    $updatednav = self::MakeChapterNav($update['prev'],$update['group'],$update['next']);
                    $this->ejsdoc->SetChapterNav($updatednav);
                    continue;
                }

                $page = KB_Page::Load($update['id']);
                if(!$page)
                {
                    continue;
                }
                $page->UpdateRefsNew($update['group'],$update['prev'],$update['next']);
                if($update['group']!=$gid)
                {
                    $groupPage = KB_Page::Load($update['group']);
                    if($groupPage)
                    {
                        // update index!!!!!!!!!!
                    }
                    $gid=$update['group'];
                }
            }    
        }
        return $updatednav;
    }
    
    public static function readLinksAndMovePage($id,$input,$matches)
    {
        $output = $input;
        $grouptags = KB_Page::doGroupLinks($output, $matches);
        //$ttt=KBPageSequence::Load($grouptags['group']);
        //var_dump($ttt);
        //var_dump($ttt->pages);
        $updates = KBPageSequence::ProcessMove($id,$grouptags['group'],$grouptags['prev'],$grouptags['next']);
        //var_dump($updates);
        foreach($updates as $update)
        {
            
            
            
            $page = KB_Page::Load($update['id']);
            if(!$page)
            {
                continue;
            }
            if($update['id']==$id)
            {
                $output = KB_Page::updateGroupLinks($grouptags,$update,$output);
                $page->raw=$output;
            }
            $page->UpdateRefs($update['group'],$update['prev'],$update['next']);
            
        }
        //die;
        return $output;
    }
    public static function GetLinkMarkupMatches($input)
    {
        
        $matchesbuffer=[];
        preg_match_all("/\[\[[\w]{1,}[|][^\]]{0,}\]\]/",$input,$matchesbuffer);

        $matches=[];
        foreach($matchesbuffer[0] as $match)
        {
            $line=explode("|",substr($match,2,-2));
            $line[]=$match;
            $matches[]=$line;
        }
        return $matches;
    }
    public static function ProcessMarkup($id, $input,$passive=false)
    {
        /*
         * The rest of the fucking owl...
         * 
         */
        $output = $input;
        $matches = KB_Page::GetLinkMarkupMatches($input);
        if(!$passive)
        {
            $output = KB_Page::readLinksAndMovePage($id,$output,$matches);
            return;
        }
        
        $output=KB_Page::doSpecialLinks($output,$matches);
        $matches = array_filter($matches);
        $output=KB_Page::process_links($output,$matches);
        return $output;
    }
}