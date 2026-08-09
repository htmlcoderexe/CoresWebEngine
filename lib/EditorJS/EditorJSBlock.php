<?php


class EditorJSBlock
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
