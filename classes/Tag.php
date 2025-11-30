<?php

Module::DemandProperty("tag","Tag","A tag that is applied to this object");
/**
 * Description of Tag
 *
 * @author admin
 */
class Tag
{
    /**
     * Attaches a tag to a specific object.
     * @param int $EVAID Object ID to attach the tag to.
     * @param string $tag Tag to attach.
     * @return bool True if the tag was added, false if the tag was already present.
     */
    public static function Attach($EVAID,$tag,$type="generic")
    {
        //$e=new EVA($EVAID);
        $existing = self::GetTags($EVAID);
        self::Remove($EVAID,$tag);
        DBHelper::Insert("eva_tags",[$EVAID, $type, $tag]);
        return !in_array($tag,$existing);
    }
    
    /**
     * Removes a tag from a specific object.
     * @param int $EVAID Object ID to remove the tag from.
     * @param string $tag Tag to remove.
     * @return void
     */
    public static function Remove($EVAID,$tag)
    {
        DBHelper::Delete("eva_tags", ["tag"=>$tag,"evaid"=>$EVAID]);
        return;
    }
    
    /**
     * Searches for objects of a type matching given tag(s).
     * @param string $EVAType type of object to search.
     * @param string[]|string $tag Either a single tag as a string or an array
     * of tag strings.
     * @return array of EVA object IDs matching the search.
     */
    public static function Find($EVAType,$tag)
    {
        // have to do a special query if looking for objects with all of these tags
        if(is_array($tag))
        {
            $valuesstring = "(";
            $valuesstring .= str_repeat("?,", count($tag) - 1);
            $valuesstring .= "?)";
            
            $q= "SELECT evaid FROM `eva_tags`  WHERE tag in $valuesstring "
               . "AND evatype = ?"
               ."GROUP BY evaid "
               ."HAVING COUNT(evaid) = ?";
            $params = $tag;
            $params[]= $EVAType;
            $params[]= count($tag);
            return DBHelper::RunList($q, $params);
        }
        // just search for the one tag and return
        else
        {
            $query = DBHelper::Select("eva_tags", ["evaid"],["tag"=>$tag,"evatype"=>$EVAType]);
            return DBHelper::RunList($query, [$tag,$EVAType]);
        }
    }
    
    /**
     * Retrieves tags associated with a specific object.
     * @param int $EVAID Object ID to find the tags for.
     * @return array of tags found.
     */
    public static function GetTags($EVAID)
    {
        $query = DBHelper::Select("eva_tags", ["tag"],["evaid"=>$EVAID]);
        return DBHelper::RunList($query, [$EVAID]);
    }
    
    /**
     * Searches for existing tags matching a given prefix.
     * @param string $prefix Prefix for the tags to be matched to.
     * @param string $evatype If set, only search for tags applied to this object type.
     * @param int $exclude If nonzero, do not return tags applying only to this object.
     * @param bool $droptype If true, strip the tag "type" (part before ":") from the returned results.
     * @return array of tags found.
     */
    public static function GetSuggestions($prefix, $evatype="", $exclude=0, $droptype=false)
    {
        $excludestring = "";
        $evatypestring = "";
        $params = ["%" . $prefix . "%"];
        if($evatype!=="")
        {
            $evatypestring = " AND evatype = ?";
            $params[] = $evatype;
        }
        // {@todo make this exclude tags already present on the exceluded ID regardless of their presence elsewhere}
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
                // {@todo make this handle "bare" tags properly}
                $suggestions[$i] = explode(":", $suggestions[$i])[1];
            }
        }
        return $suggestions;
    }
}
