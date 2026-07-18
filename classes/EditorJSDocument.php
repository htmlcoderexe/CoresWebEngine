<?php


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
    public static function FromBlocks($blocks)
    {
        $result = new EditorJSDocument();
        $result->blocks = $blocks;
        $result->RefreshImageList();
        $result->GetPlainText();
        return $result;
    }
    
    public static function ExtractFromList($list)
    {
        $acc ="";
        $acc.=$list['content'] ?? "";
        if(isset($list['items']) && count($list['items'])>0)
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
    
    public function UpdateImages($imagemap)
    {
        $this->images = [];
        $c=count($this->blocks);
        for($i=0;$i<$c;$i++)
        {
            if($this->blocks[$i]['type']=="image")
            {
                if(isset($imagemap[$this->blocks[$i]['data']['url']]))
                {
                    $this->blocks[$i]['data']['url']=$imagemap[$this->blocks[$i]['data']['url']];
                }
                $this->images[]=$this->blocks[$i]['data']['url'];
            }
        }
    }
    
    public function GetIndexBlock()
    {
        foreach($this->blocks as $block)
        {
            if($block['type'] == 'chapterindex')
            {
                return $block;
            }
        }
    }
    
    public function GetChapterNav()
    {
        foreach($this->blocks as $block)
        {
            if($block['type'] == 'chapternav')
            {
                return $block;
            }
        }
    }
    public function SetChapterNav($newnav)
    {
        $c=count($this->blocks);
        for($i=0;$i<$c;$i++)
        {
            if($this->blocks[$i]['type'] == 'chapternav')
            {
                $this->blocks[$i]=$newnav;
                return true;
            }
        }
        array_unshift($this->blocks,$newnav);
        return false;
    }
    public function RemoveChapterNav()
    {
        $c=count($this->blocks);
        for($i=0;$i<$c;$i++)
        {
            if($this->blocks[$i]['type'] == 'chapternav')
            {
                array_splice(array: $this->blocks, offset: $i, length: 1);
                return;
            }
        }
    }
}
