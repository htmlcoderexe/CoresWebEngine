<?php
class KB_Page
{
    private $raw;
    private $processed;
    private $title;
    private $id;
    public function __construct($id=-1)
    {
        if($id == -1)
        {
            return;
        }
        $page=KB_Page::GetLastRevision($id);
        $this->raw=$page['content_raw'];
        $this->id=$id;
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
    public static function CreatePage($title)
    {
        DBHelper::$DBLink->beginTransaction();
        DBHelper::Insert('kb_pages',Array(null,$title,time(),0,time(),1));
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
    public static function process_links($input)
    {
        $matchesbuffer=Array();
        //$vars=Array();
        preg_match_all("/\[\[[\w]{1,}[|][^\]]{0,}\]\]/",$input,$matchesbuffer);

        $matches=$matchesbuffer[0];

        for($i=0;$i<count($matches);$i++)
        {
            $linkmeat=substr($matches[$i],2,-2);
            list($key,$default)=explode("|",$linkmeat);
            /*     	if(!isset($vars[$key])) 
            { 
                    $vars[$key]=$default;
            }
            //*/
            $tpl="<a href=\"/kb/view/%s\">%s</a>";
            $input=str_replace($matches[$i],sprintf($tpl,$key,$default),$input);
        }
        return $input;
    }
    public static function ProcessMarkup($input)
    {
        $output="";
        /*
         * The rest of the fucking owl...
         * 
         */
        $output.=KB_Page::process_links($input);
        return $output;
    }
}