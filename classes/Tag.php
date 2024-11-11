<?php

Module::DemandProperty("tag","Tag","A tag that is applied to this object");
/**
 * Description of Tag
 *
 * @author admin
 */
class Tag
{
    public static function Attach($EVAID,$tag)
    {
        $e=new EVA($EVAID);
        $e->EraseAttribute("tag",$tag);
        $e->AddAttribute("tag",$tag);
        $e->Save();
    }
    
    public static function Remove($EVAID,$tag)
    {
        $e=new EVA($EVAID);
        $e->EraseAttribute("tag",$tag);
        $e->Save();
    }
    
    public static function Find($EVAType,$tag)
    {
        return EVA::GetByProperty("tag", $tag, $EVAType);
    }
    
    public static function GetTags($EVAID)
    {
        return EVA::LoadPropFromDB($EVAID,"tag");
    }
}
