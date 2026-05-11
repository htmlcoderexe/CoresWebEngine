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
