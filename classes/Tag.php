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
        self::Remove($EVAID,$tag);
        DBHelper::Insert("eva_tags",[$EVAID, $e->type, $tag]);
        return;
        
        $e->EraseAttribute("tag",$tag);
        $e->AddAttribute("tag",$tag);
        $e->Save();
    }
    
    public static function Remove($EVAID,$tag)
    {
        DBHelper::Delete("eva_tags", ["tag"=>$tag,"evaid"=>$EVAID]);
        return;
        
        $e=new EVA($EVAID);
        $e->EraseAttribute("tag",$tag);
        $e->Save();
    }
    
    public static function Find($EVAType,$tag)
    {
        $query = DBHelper::Where(["tag"=>$tag,"evatype"=>$EVAType]);
        return DBHelper::RunList($query, [$tag,$EVAType]);
        
        return EVA::GetByProperty("tag", $tag, $EVAType);
    }
    
    public static function GetTags($EVAID)
    {
        $q = DBHelper::Where(["evaid"=>$EVAID]);
        return DBHelper::RunList($q, [$EVAID]);
        
        return EVA::LoadPropFromDB($EVAID,"tag");
    }
    
    public static function GetSuggestions($prefix, $evatype="", $exclude=0)
    {
        $excludestring = "";
        $evatypestring = "";
        $params = [$prefix . "%"];
        if($evatype!=="")
        {
            $evatypestring = " AND evatype = ?";
            $params[] = $evatype;
        }
        if($exclude !=0)
        {
            $excludestring = " AND evaid <> ?";
            $params[] = intval($exclude);
        }
        $q = "SELECT tag, COUNT(*)"
                . " FROM eva_tags"
                . " WHERE tag LIKE ?" . $evatypestring . $excludestring
                . " GROUP BY tag"
                . " ORDER BY 2 DESC";
        $suggestions = DBHelper::RunList($q,$params);
        return $suggestions;
    }
}
