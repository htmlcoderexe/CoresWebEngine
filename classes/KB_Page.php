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
        $doc = self::ProcessIndexBlock($this->ejsdoc,$this->id);
        $chapternav = $this->ejsdoc->GetChapterNav();
        if($chapternav)
        {
            $doc = self::ProcessChapterNav($this->ejsdoc);
        }
        
        $this->raw = $this->ejsdoc->GetPlainText();
        $this->processed = $doc->GetHTML();
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
    
    public static function GenerateIndexBlock($id)
    {
        $cat=KBPageSequence::Load($id);
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
        for($i=0;$i<count($cat->pages);$i++)
        {
            $page = KB_Page::Load($cat->pages->items[$i]['entityId']);
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
                        Logger::log("updated group page");
                        $groupPage->ProcessDoc();
                        $groupPage->Save();
                    }
                    $gid=$update['group'];
                }
            }    
        }
        return $updatednav;
    }
    
    
    //////////////////////////////////////////////////////////////////////
    //////// legacy stuff remove once not needed
    
    
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
}