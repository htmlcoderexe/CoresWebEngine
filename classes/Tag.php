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
        $existing = self::GetTags($EVAID);
        self::Remove($EVAID,$tag);
        DBHelper::Insert("eva_tags",[$EVAID, $e->type, $tag]);
        return !in_array($tag,$existing);
    }
    
    public static function Remove($EVAID,$tag)
    {
        DBHelper::Delete("eva_tags", ["tag"=>$tag,"evaid"=>$EVAID]);
        return;
    }
    
    public static function Find($EVAType,$tag)
    {
        $query = DBHelper::Select("eva_tags", ["evaid"],["tag"=>$tag,"evatype"=>$EVAType]);
        #$query = DBHelper::Where(["tag"=>$tag,"evatype"=>$EVAType]);
        return DBHelper::RunList($query, [$tag,$EVAType]);
    }
    
    public static function GetTags($EVAID)
    {
        $query = DBHelper::Select("eva_tags", ["tag"],["evaid"=>$EVAID]);
        return DBHelper::RunList($query, [$EVAID]);
    }
    
    public static function GetSuggestions($prefix, $evatype="", $exclude=0, $droptype=false)
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
        if($droptype)
        {
            for($i=0;$i<count($suggestions);$i++)
            {
                $suggestions[$i] = explode(":", $suggestions[$i])[1];
            }
        }
        return $suggestions;
    }
}
