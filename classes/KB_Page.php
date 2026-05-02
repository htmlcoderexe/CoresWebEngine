<?php
class KB_Page
{
    private $raw;
    private $processed;
    
    public $last_revision;
    public $title;
    public $id;
    public $project_id;
    
    public $created;
    public $modified;
    public $creator;
    
    public function __construct($id,$title,$content_raw,$content_html,$project_id = 0)
    {

        $this->title=$title;
        $this->raw=$content_raw;
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
        $content = KB_Page::GetLastRevision($id);
        if($content)
        {
            $raw=$content["content_raw"];
            $html=$content["content_html"];
        }
        $result = new KB_Page($id,$page['title'],$raw,$html,$page['project_id']);
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
    public static function  SaveToDatabase($id,$text)
    {
        $processed=KB_Page::ProcessMarkup($text);
        $d=Array(null,$id,$text,$processed,time(),0);
        DBHelper::Insert('kb_page_revisions',$d);
        //Utility::ddump(mysql_error());
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
    public function ProcessHTML()
    {

    }
    public function GetRaw()
    {
        return $this->raw;
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
        $rev=DBHelper::RunRow("SELECT id,content_raw,content_html FROM kb_page_revisions WHERE page_id=? ORDER BY timestamp DESC",[$id]);
        return $rev;
    }
    public static function GetSpecificRevision($id)
    {
        //note, this does not check if any revision belongs to the page yet.
        $q=DBHelper::Select("kb_page_revisions",['id','content_raw','content_html'],['id'=>$id],['timestamp'=>'DESC']);
        $rev=DBHelper::RunRow($q,[$id]);
        return $rev['content_html'];
    }
    static function doSpecialLinks($input,&$matches)
    {
        $output = $input;
        $nextId=-1;
        $prevId=-1;
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
        $prevlinktpl = "<div class=\"prevlink\"><a href=\"/kb/view/%s\">&lt; %s</a></div>";
        $nextlinktpl = "<div class=\"nextlink\"><a href=\"/kb/view/%s\">%s &gt;</a></div>";
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
        if($nextId>0 || $prevId>0)
        {
            $topnavtpl="<nav id=\"kb_top_nav\">%s%s</nav>\n";
            
            $bottomnavtpl="\n<nav id=\"kb_bottom_nav\">%s%s</nav>";
            
            $topnav=sprintf($topnavtpl,$prevlink,$nextlink);
            $bottomnav=sprintf($bottomnavtpl,$prevlink,$nextlink);
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
    public static function ProcessMarkup($input)
    {
        /*
         * The rest of the fucking owl...
         * 
         */
        $matchesbuffer=[];
        preg_match_all("/\[\[[\w]{1,}[|][^\]]{0,}\]\]/",$input,$matchesbuffer);

        $matches=[];
        foreach($matchesbuffer[0] as $match)
        {
            $line=explode("|",substr($match,2,-2));
            $line[]=$match;
            $matches[]=$line;
        }
        
        $output=KB_Page::doSpecialLinks($input,$matches);
        $matches = array_filter($matches);
        $output=KB_Page::process_links($output,$matches);
        return $output;
    }
}