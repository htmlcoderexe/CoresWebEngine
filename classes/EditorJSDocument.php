<?php

class EJSBlock
{
    private $block;
    public function __construct($block)
    {
        $this->block = $block;
    }
    
    public function Get($keyname, $datakeyname = 'data')
    {
        if(!isset($this->block[$datakeyname][$keyname]))
        {
            return null;
        }
        return $this->block[$datakeyname][$keyname];
    }
}

class EditorJSDocumentFormatter
{
    public const TEMPLATES = [
        'paragraph'=>"<p>%s</p>\n",
        'quote'=>"<blockquote>%s</blockquote>\n",
        'code'=>"<blockquote><pre><code>%s</code></pre></blockquote>\n",
        'header'=>'<h%2$s>%1$s</h%2$s>',
        'ol'=>"<ol>%s</ol>\n",
        'ul'=>"<ul>%s</ul>\n",
        'li'=>"\t<li>%s</li>\n",
        'image'=>"<p class=\"description\"><img src=\"%s\" />%s</p>\n",
        'table'=>"<table>%s</table>\n",
        'tr'=>"\t<tr>%s</tr>\n",
        'th'=>"\t\t<th>%s</th>\n",
        'td'=>"\t\t<td>%s</td>\n",
        'chapternav'=>"<nav id=\"kb_%s_nav\">%s</nav>\n",
        'navlink'=>"\t<div class=\"%slink\"><a href=\"/kb/view/%s\">%s</a></div>\n"
    ];
    
    public static function DoBlock($block)
    {
        $type = $block['type'] ?? "";
        if($type == "")
        {
            return "";
        }
        switch($type)
        {
            case 'paragraph':
                return self::Paragraph($block);
            case 'header':
                return self::Header($block);
            case 'quote':
                return self::Quote($block);
            case 'code':
                return self::Code($block);
            case 'list':
                return self::List($block);
            case 'table':
                return self::Table($block);
            case 'image':
                return self::Image($block);
            case 'chapternav':
                return self::Chapternav($block);
        }
    }
    
    
    public static function Paragraph($block)
    {
        $text = (new EJSBlock($block))->get('text') ?? "";
        return sprintf(self::TEMPLATES['paragraph'],$text);
    }
    
    public static function Header($block)
    {
        $b=new EJSBlock($block);
        $text = $b->get('text') ?? "";
        $level = $b->get('level') ?? 2;
        return sprintf(self::TEMPLATES['header'],$text,$level);
    }
    
    public static function List($block)
    {
        $b=new EJSBlock($block);
        $items = $b->get('items');
        $type = $b->get('style');
        $c = count($items);
        $content = "";
        if($c<1)
        {
            return "";
        }
        for($i=0;$i<$c;$i++)
        {
            $content.=sprintf(self::TEMPLATES['li'],$items[$i]['content']);
        }
        $result = sprintf(self::TEMPLATES[$type == 'unordered' ? 'ul' : 'ol'],$content);
        return $result;
    }
    
    public static function Image($block)
    {
        $b=new EJSBlock($block);
        $src=$b->get('url');
        $caption = $b->get('caption');
        if($caption && $caption !="")
        {
            $caption = "<br />".$caption;
        }
        $result = sprintf(self::TEMPLATES['image'],$src,$caption);
        return $result;
    }
    
    public static function Quote($block)
    {
        $text = (new EJSBlock($block))->get('text') ?? "";
        return sprintf(self::TEMPLATES['quote'],$text);
    }
    
    public static function Code($block)
    {
        $text = (new EJSBlock($block))->get('code') ?? "";
        return sprintf(self::TEMPLATES['code'],$text);
    }
    
    public static function Table($block)
    {
        $b=new EJSBlock($block);
        $table = $b->get('content');
        $headings = $b->get('withHeadings');
        $c=count($table);
        if($c<1)
        {
            return "";
        }
        $content = "";
        for($i=0;$i<$c;$i++)
        {
            $rowcontent = "";
            $isheader = $headings && $i == 0;
            $row = $table[$i];
            $cc = count($row);
            if($cc < 1)
            {
                continue;
            }
            for($j=0;$j<$cc;$j++)
            {
                
                $rowcontent.=sprintf(self::TEMPLATES[$isheader ? 'th' : 'td'],$row[$j]);
            }
            $content.=sprintf(self::TEMPLATES['tr'], $rowcontent);
        }
        $result = sprintf(self::TEMPLATES['table'], $content);
        return $result;
    }
    // the preprocessed version!!
    public static function ChapterNav($block)
    {
        $b=new EJSBlock($block);
        $previd=$b->get('prev') ?? -1;
        $nextid=$b->get('next') ?? -1;
        $indexid=$b->get('index') ?? -1;
        $prevtitle = $b->get('prevtitle') ?? 'Previous';
        $nexttitle = $b->get('nexttitle') ?? 'Next';
        $indextitle = $b->get('indextitle') ?? 'Index';
        $prevlink = "";
        $nextlink = "";
        $indexlink = "";
        $navpos = $b->get('bottom') ? 'bottom' : 'top'; 
        $nav = "";
        if($previd!=-1 && $prevtitle)
        {
            $prevlink = sprintf(self::TEMPLATES['navlink'],'prev',$previd,$prevtitle);
        }
        if($nextid!=-1 && $nexttitle)
        {
            $nextlink = sprintf(self::TEMPLATES['navlink'],'next',$nextid,$nexttitle);
        }
        if($indexid!=-1 && $indextitle)
        {
            $indexlink = sprintf(self::TEMPLATES['navlink'],'index',$indexid,$indextitle);
        }
        $links = $prevlink.$indexlink.$nextlink;
        if($links!="")
        {
            $nav = sprintf(self::TEMPLATES['chapternav'],$navpos,$links);
        }
        return $nav;
    }
    
}

/**
 * Description of EditorJSDocument
 *
 * 
 */
class EditorJSDocument
{
    public $blocks = [];
    public $images = [];
    private $plaintext = "";
    public static function FromJSON($json)
    {
        $data = json_decode($json,true);
        if(!isset($data['blocks']))
        {
            return null;
        }
        $result = new EditorJSDocument();
        $result->blocks = $data['blocks'];
        $result->RefreshImageList();
        $result->GetPlainText();
        return $result;
    }
    
    public static function ExtractFromList($list)
    {
        $acc ="";
        $acc.=$list['content'] ?? "";
        if($list['items'] && count($list['items'])>0)
        {
            foreach($list['items'] as $item)
            {
                $acc.=self::ExtractFromList($item);
            }
        }
        return $acc."\n";
    }
    
    
    public static function BlockPlainText($block)
    {
        $result ="";
        switch($block['type'])
        {
            case "paragraph":
            case "header":
            case "quote":
            {
                $result.=$block['data']['text'];
                break;
            }
            case "list":
            {
               $result.=self::ExtractFromList($block['data']);
               break;
            }
            case "image":
            {
                $result.=$block['data']['caption'];
                break;
            }
        }
        return strip_tags($result);
    }
    
    public function GetHTML()
    {
        $content ="";
        foreach($this->blocks as $block)
        {
            $content.=EditorJSDocumentFormatter::DoBlock($block);
        }
        return $content;
    }
    
    public function GetPlainText($refresh = false)
    {
        if($this->plaintext == "" || $refresh)
        {
            $this->plaintext = "";
            foreach($this->blocks as $block)
            {
                $this->plaintext.=self::BlockPlainText($block)."\n";
            }
        }
        return $this->plaintext;
    }
    
    public function RefreshImageList()
    {
        $this->images = [];
        foreach($this->blocks as $block)
        {
            if($block['type']=="image")
            {
                $this->images[]=$block['data']['url'];
            }
        }
    }
}
