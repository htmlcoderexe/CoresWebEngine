<?php
class EVA
{
	public $id;
	public $type;
	public $attributes;
	public $proplist;
        public $children;
        public $dirty;
        private $eraselist;
        
        
        public const OWNER_NOBODY = 0;
        public const OWNER_CURRENT = -1;
        
        /**
         * Loads an EVA object from ID
         * @param int $id EVAID of the object
         * @return \EVA The object found by ID if exists
         */
	function __construct($id)
	{
            $stmt = "
            SELECT id,type
            FROM eva_objects
            WHERE id=?";
            //$stmt->bindParam(1,$id);
            //$objectmeta=DBHelper::GetArray($stmt);
            $data=DBHelper::RunTable($stmt,[$id]);
            if(count($data)<=0)
            {
                return;
            }
            $this->id=$id;
            $this->type=$data[0]['type'];
            $this->proplist=EVA::GetPropList($id);
            $this->attributes=EVA::GetFullObject($this->proplist);
            $this->eraselist = [];
            $this->dirty = false;
	}
	
        /**
         * Loads an EVA object from ID, optionally checking type
         * @param int $id EVAID to load
         * @param string $type if not empty, will check if the type matches
         * @return null|\EVA the loaded EVA object, null if it doesn't exist or if the type is specified and doesn't match
         */
        public static function Load($id, $type="")
        {
            if(!self::Exists($id, $type))
            {
                return null;
            }
            return new EVA($id);
        }
        
        /**
         * Creates a new EVA object of a specified type
         * @param string $type Object typename
         * @param int $owner Sets the owner of the created object. -1 uses current uid and 0 uses nobody.
         * @param array $blueprint List of attributes to preload on the object as a flat array,
         * or an associative array of the form
         * ['attribute name'] => ['attribute value']
         * @return \EVA The created object
         */
	public static function CreateObject($type,$owner = self::OWNER_CURRENT,$blueprint=[])
	{
            if($owner === self::OWNER_CURRENT)
            {
                $currentuid = User::GetCurrentUser()->userid;
                if(User::GetCurrentUser()->IsGuest())
                {
                    $currentuid=self::OWNER_NOBODY;
                }
                $owner = $currentuid;
            }
            
            DBHelper::Insert('eva_objects',Array(null,$type,$owner));
            $objid=DBHelper::GetLastId();
            $object= new EVA($objid);
            $object->attributes=[];
            $object->proplist=[];
            if(isset($blueprint[0]))
            {
                while(count($blueprint)>0)
                {
                    $prop=array_shift($blueprint);
                    $object->AddAttribute($prop,'');
                }
            }
            elseif(is_array($blueprint))
            {
                foreach($blueprint as $attribute=>$value)
                {
                    $object->AddAttribute($attribute,$value);
                }
            }
            $object->Save();
            return $object;
	}
        
        public static function DeleteObject($id)
        {
            $stmt = "
            SELECT id,type
            FROM eva_objects
            WHERE id=?";
            //$stmt->bindParam(1,$id);
            //$objectmeta=DBHelper::GetArray($stmt);
            $data=DBHelper::RunTable($stmt,[$id]);
            if(count($data)<=0)
            {
                return;
            }
            
            $type=$data[0]['type'];
            DBHelper::Update("eva_objects",["type"=>"!".$type],["id"=>$id]);
            
        }
        
        /**
         * Creates a new EVA attribute.
         * @param string $name Name used to refer to the attribute.
         * @param string $dname Friendly display name.
         * @param string $desc Description of the property.
         */
        public static function CreateProperty($name,$dname,$desc)
        {
            
            DBHelper::Insert('eva_properties',[null,$name,$dname,$desc]);
        }
        
	
        /**
         * Find an attribute's internal ID
         * @param string $propname Attribute name
         * @return int|bool ID of the attribute if it exists or false otherwise
         */
        public static function GetPropertyId($propname)
	{
		$query="SELECT id
		FROM eva_properties
		WHERE name=?";
		return DBHelper::RunScalar($query, [$propname]);
	}
        
        
        //=========BEGIN Attribute Manipulation=========
        //
        //      These functions are directly writing to the
        //      database and are mostly used internally
        //      by the object instance functions
        
	/**
         * Retrieves a list of attribute/value pairs for a given object.
         * @param int $id EVA object ID to fetch.
         * @return array An array of associative arrays of the form:
         * ['name'] => "attribute name",
         * ['value'] => "attribute value"
         * This can be passed to EVA::GetFullObject to get an associative array.
         */
	public static function GetPropList($id)
	{
            $stmt = "
            SELECT name,value,v.id as pid,property_id 
            FROM eva_property_values v
            INNER JOIN eva_properties p
            ON p.id = property_id
            WHERE object_id=?
            ";
            $props=DBHelper::RunTable($stmt,[$id]);
            if(count($props)==0)
            {
                return [];
            }
            return $props;
	
	}
	
        
        /**
         * Returns an array of values of a specific property of a specific EVA object.
         * @param int $objid The object to search
         * @param string $propname Property name to search for
         * @return array An array containing any values found
         */
        public static function LoadPropFromDB($objid,$propname)
        {
            $query="SELECT map.value
		FROM eva_property_values map
		WHERE object_id=?
		AND map.property_id=
		(SELECT id FROM eva_properties WHERE eva_properties.name=?)";
            return DBHelper::RunList($query, [$objid,$propname], 0);
        }
        /**
         * Writes a value to an attribute by its name
         * If the object doesn't have the attribute, it is created.
         * If the object has multiple values for the attribute, the first one gets overwritten.
         * @param int $objid EVA object ID
         * @param string $propname Property name to write
         * @param scalar $value Value to write
         */
        public static function WritePropByName($objid,$propname,$value)
        {
            $query="SELECT map.value, map.id
		FROM eva_property_values map
		WHERE object_id=?
		AND map.property_id=
		(SELECT id FROM eva_properties WHERE eva_properties.name=?)";
            $check = DBHelper::RunRow($query, [$objid,$propname]);
            if($check!==false)
            {
                $recordid = (int) $check['id'];
                EVA::UpdateProperty($recordid,$value);
            }
            else
            {
                $propid = self::GetPropertyId($propname);
                if($propid)
                {
                    self::AppendProperty($objid, $propid, $value);
                }
            }
        }
        
        /**
         * Reformats the Name/Value pair array into an associative
         * array indexable by attribute name. Any attributes with multiple values
         * get converted into an array containing the values.
         * @param array $props An array of associative arrays of this structure:
         * ['name'] => "attribute name",
         * ['value'] => "attribute value"
         * @return array of this structure:
         * ['attribute name'] => "attribute value"
         */
	public static function GetFullObject($props)
	{
		
		$result= Array();
                if(!$props)
                {
                    return $result;
                }
		for($i=0;$i<count($props);$i++)
		{
			if(isset($result[$props[$i]['name']]))
			{
				if(is_array($result[$props[$i]['name']]))
				{
					$result[$props[$i]['name']][]=$props[$i]['value'];
				}
				else
				{
					$tmp=$result[$props[$i]['name']];
					$result[$props[$i]['name']]=Array();
					$result[$props[$i]['name']][]=$tmp;
					$result[$props[$i]['name']][]=$props[$i]['value'];
				}
			}
			else
			{
				$result[$props[$i]['name']]=$props[$i]['value'];
			}
		}
		return $result;
	}
	
        /**
         * Modifies the value of a specific attribute value entry.
         * You probably want to use the SetSingleAttribute method on a specific object.
         * @param int $id ID of the specific entry
         * @param scalar $value the new value
         */
	public static function UpdateProperty($id,$value)
	{
            
		$stmt = DBHelper::$DBLink->prepare("UPDATE eva_property_values
		SET value=? 
		WHERE id=?");
                $stmt->bindParam(1,$value);
                $stmt->bindParam(2, $id);
                $stmt->execute();
	}
	
        /**
         * Adds a property assigned to a specific EVA object
         * @param int $objId EVA object ID
         * @param id $id property ID in eva_properties
         * @param string $value value to be written
         * @return int ID of the new property entry in eva_property_map
         */
	public static function AppendProperty($objId, $id,$value)
	{
		$row=Array(
			null,
			$objId,
			$id,
			$value
		
		);
		DBHelper::Insert('eva_property_values',$row);
		return DBHelper::GetLastId();
	}
        
        /**
         * Deletes a specific attribute value entry by ID.
         * You want to use the EraseAttribute method on the actual object.
         * @used-by EVA::EraseAttribute
         * @param int $entryId ID of the specific attribute value entry.
         */
        public static function DeleteAttribute($entryId)
        {
            DBHelper::Delete("eva_property_values",['id'=>$entryId]);
        }
        
        //=========END Attribute Manipulation===========
        
        //=========BEGIN Children list Manipulation=====
        //
        //      Functions here deal with linking 
        //      objects to each other as parent/child
        //
        //
        
        /**
         * Gets all children of this object, optionally filtered by type
         * Only direct links are retrieved without recursion.
         * @param int $id object ID to retrieve children from
         * @param string $child_type type of child object to filter by
         * @return array of IDs of found children.
         */
        public static function GetChildren($id, $child_type="")
        {
            $filter =["parent_id"=>$id];
            $params = [$id];
            if($child_type)
            {
                $filter["child_type"]=$child_type;
                $params[]= $child_type;
            }
            $q = DBHelper::Select("eva_mappings", ["child_id"], $filter);
            $child_list = DBHelper::RunList($q,$params);
            return $child_list;
        }
        
        /**
         * Gets all parents of this object, optionally filtered by type
         * Only direct links are retrieved without recursion.
         * @param int $id object ID to retrieve parents from
         * @param string $parent_type type of parent object to filter by
         * @return array of IDs of found parents.
         */
        public static function GetParents($id, $parent_type="")
        {
            $filter =["child_id"=>$id];
            $params = [$id];
            if($parent_type)
            {
                $filter["parent_type"]=$parent_type;
                $params[]= $parent_type;
            }
            $q = DBHelper::Select("eva_mappings", ["parent_id"], $filter);
            $parent_list = DBHelper::RunList($q,$params);
            return $parent_list;
        }
        
        /**
         * Adds a child to a parent object.
         * @param int $parent Parent object's ID
         * @param int $child Child object's ID
         * @return bool True if the relationship exists now, false otherwise.
         */
        public static function AddRelation($parent, $child)
        {
            $grandparents = self::GetParents($parent);
            $parents = self::GetParents($child);
            // if the reverse relationship already exists, fail
            if(in_array($child, $grandparents))
            {
                
                Logger::log("EVA::AddRelation: [$child] is a parent of [$parent]",Logger::TYPE_WARNING,"EVA Object Parenting");
           
                return false;
            }
            // return true, but do nothing if the relationship already exists
            if(in_array($parent, $parents))
            {   Logger::log("$child is already a child of $parent",Logger::TYPE_WARNING,"EVA Object Parenting");
           
                return true;
            }
            // load and verify both objects
            $eva_child = new EVA($child);
            $eva_parent = new EVA($parent);
            // if either or both are invalid, fail
            if($eva_child->id < 1 || $eva_parent->id < 1)
            {
                Logger::log("Either [$child] ({$eva_child->id}) or [$parent] ({$eva_child->id}) invalid.",Logger::TYPE_WARNING,"EVA Object Parenting");
           
                return false;
            }
            // all good, write to db
            DBHelper::Insert("eva_mappings",[$eva_parent->type, $eva_child->type, $eva_parent->id, $eva_child->id]);
            return true;
        }
        
        /**
         * Removes a parent/child relation.
         * @param int $parent Parent object's ID
         * @param int $child Child object's ID
         */
        public static function RemoveRelation($parent, $child)
        {
            DBHelper::Delete("eva_mappings",['parent_id'=>$parent,'child_id'=>$child]);
        }
        
        // helper methods to be used on instances
        // take effect immediately unlike other modifications
        
        /**
         * Adds an object as a child of itself.
         * @param int $id Object ID to add.
         */
        public function Adopt($id)
        {
            self::AddRelation($this->id, $id);
        }
        
        /**
         * Removes itself as a child of a specific object.
         * @param int $id Parent object ID to remove from.
         */
        public function Emancipate($id)
        {
            self::RemoveRelation($id, $this->id);
        }
        
        /**
         * Removes a child from itself.
         * @param int $id Child object ID to remove.
         */
        public function Disown($id)
        {
            self::RemoveRelation($this->id, $id);
        }
        
        /**
         * Removes all parents from this object.
         */
        public function Orphan()
        {
            $parents = self::GetParents($this->id);
            foreach($parents as $parent)
            {
                $this->Emancipate($parent);
            }
        }
        
        /**
         * Removes all children from this object.
         */
        public function DisownAll()
        {
            $children = self::GetChildren($this->id);
            foreach($children as $child)
            {
                $this->Disown($child);
            }
        }
        
	//=========END Children list Manipulation=======
        
        //=========BEGIN Search and Aggregation=========
        //      
        //      These functions allow for searching
        //      and querying the objects through the
        //      database itself instead of loading
        //      each object separately
        //
        
        /**
         * Checks if a given EVA object exists.
         * @param int $id ID to check.
         * @param string $type If specified, also checks if the object is of this type.
         * @return bool True if the object exists, false otherwise.
         */
        public static function Exists($id, $type="")
        {
            $where = ['id'=>$id];
            $params = [$id];
            if($type)
            {
                $where['type'] = $type;
                $params[] = $type;
            }
            $q= DBHelper::Select("eva_objects", ["id"], $where);
            return (bool)DBHelper::RunList($q, $params);
        }
        
        /**
         * Gets EVA objects that have a specific property set to a specific value, filtered by object type.
         * @param string $property Name of the target property
         * @param string $value Value to be searched for
         * @param string $type Object type to filter by
         * @return array of matching EVA IDs if any found
         */
	public static function GetByProperty($property,$value,$type)
	{
            EngineCore::Lap2Debug("running for $type with '$property' = '$value'");
            $query ="
		SELECT DISTINCT object_id FROM eva_property_values
		INNER JOIN eva_objects
		ON eva_objects.id = object_id
		INNER JOIN eva_properties 
		ON eva_properties.id =property_id
		WHERE value =? and eva_objects.type=? and eva_properties.name=?
		
		";
            $r=DBHelper::RunList($query,[$value,$type,$property]);
            EngineCore::Lap2Debug("DONE running for $type with '$property' = '$value'");
            return $r;
	}
        
        /**
         * Gets EVA objects that have a specific property set to a specific value, filtered by object type.
         * @param string $property Name of the target property
         * @param string $value Value to be searched for
         * @param string $type Object type to filter by
         * @return array of matching EVA IDs if any found
         */
	public static function GetByPropertyPre($property,$value,$type)
	{
            EngineCore::Lap2Debug("running %LIKE% of $type");
            $query ="
		SELECT DISTINCT object_id FROM eva_property_values
		INNER JOIN eva_objects
		ON eva_objects.id = object_id
		INNER JOIN eva_properties 
		ON eva_properties.id =property_id
		WHERE value LIKE ? and eva_objects.type=? and eva_properties.name=?
		
		";
            $r=DBHelper::RunList($query,[$value."%",$type,$property]);
            EngineCore::Lap2Debug("done running %LIKE% of $type");
            return $r;
	}
        
        public static function SearchString($property,$value,$type)
        {
            EngineCore::Lap2Debug("running search on $type");
            $propId=self::GetPropertyId($property);
            if(!$propId)
            {
                return [];
            }
            $query ="
		SELECT DISTINCT object_id FROM eva_property_values
		INNER JOIN eva_objects
		ON eva_objects.id = object_id
		WHERE value LIKE ? and eva_objects.type=? and property_id=?
		
		";
            $ids = DBHelper::RunList($query,["%".$value."%",$type,$propId]);
            EngineCore::Lap2Debug("done running search");
            return $ids;
        }
        
        /**
         * Gets an array of all objects of a specific type containing
         * the value of that property per objects. Each item looks like this:
         * {
         *      ['object_id'] => 10,
         *      ['value'] => 'some value'
         * }
         * @param string $property name of the property to aggregate
         * @param string $type object type
         * @return an array of arrays each with 'object_id' and 'value'
         */
	public static function GetKVA($property,$type)
	{
            EngineCore::Lap2Debug("getting list of '$property' for $type");
            $query ="
		SELECT DISTINCT object_id,value FROM eva_property_values
		INNER JOIN eva_objects
		ON eva_objects.id = object_id
		INNER JOIN eva_properties 
		ON eva_properties.id =property_id
		WHERE eva_objects.type=? and eva_properties.name=?
		
		";
            $r=DBHelper::RunTable($query,[$type,$property]);
            EngineCore::Lap2Debug("DONE getting list of '$property' for $type");
            return $r;
	}
        
        /**
         * Gets an array of all objects of a specific type containing
         * the value of that property per objects. Each item looks like this:
         * {
         *      ['object_id'] => 10,
         *      ['value'] => 'some value'
         * }
         * @param string $property name of the property to aggregate
         * @param string $type object type
         * @return an array of arrays each with 'object_id' and 'value'
         */
	public static function GetAsTable($propertylist,$type, $list=null)
	{
            
            EngineCore::Lap2Debug("getting table of $type");
            if($list===[])
            {
                return [];
            }
            $itemlist = "";
            $origlist=$propertylist;
            if($list)
            {
                $itemlist = "AND object_id IN (?". str_repeat(",?", count($list)-1).")";
                $propertylist = array_merge($propertylist, $list);
            }
            $proplist = "?". str_repeat(",?", count($origlist)-1);
            $query ="
		SELECT DISTINCT object_id,eva_properties.name,value FROM eva_property_values
		INNER JOIN eva_objects
		ON eva_objects.id = object_id
		INNER JOIN eva_properties 
		ON eva_properties.id =property_id
		WHERE eva_objects.type=? and eva_properties.name IN ($proplist) $itemlist
		
		";
            $args=$propertylist;
            array_unshift($args,$type);
            $result = DBHelper::RunTable($query,$args);
            $output = [];
            foreach($result as $entry)
            {
                if(!isset($output[$entry['object_id']]))
                {
                    $output[$entry['object_id']] = array_fill_keys($origlist,'');
                }
                $output[$entry['object_id']][$entry['name']] = $entry['value'];
            }
            // EngineCore::Write2Debug($query);
            EngineCore::Lap2Debug("done getting table of $type");
            return $output;
	}
        
        /**
         * Gets a list of IDs of all objects of a given type
         * @param string Object type to return $type
         * @return array of object IDs
         */
        public static function GetAllOfType($type)
        {
            EngineCore::Lap2Debug("getting ALL of $type");
            $q=DBHelper::Select("eva_objects",["id"],["type"=>$type]);
            $r=DBHelper::RunList($q,[$type]);
            EngineCore::Lap2Debug("DONE getting ALL of $type");
            return $r;
        }
        
        
	//=========END Search and Aggregation===========
        
        //=========BEGIN Instance methods===============
        //      
        //      These operate on the actual objects
        //      retrieved from the database.
        //      The changes made to the object
        //      are only written to the database 
        //      with the Save() method.
        //
        
	/**
         * Retrieves indices in the object's $proplist with a matching name
         * @param string $name name of the attribute to find
         * @return int[] list of indices in $proplist containing the matching attribute
         */
	public function FindAttribute($name)
	{
		return array_keys(array_column($this->proplist,'name'),$name);
	}
	
        /**
         * Checks whether this object has a specific attribute set.
         * @param string $name Name of the attribute to find.
         * @return bool True if the attribute was found, false otherwise.
         */
	public function HasAttribute($name)
	{
		$result=$this->FindAttribute($name);
		EngineCore::Dump2Debug($result);
		return count($result)>0;
		//return isset($this->attributes[$name]);
	}
	
        /**
         * Checks whether this object has multiple values of a given attribute
         * @param string $name attribute name
         * @return bool True if the given attribute exists and has multiple values, false otherwise.
         */
	public function HasMultiple($name)
	{
		return is_array($this->attributes[$name]);
	}
	// not sure if this is used meaningfully...
	public function GetAttribute($name)
	{
		return isset($this->attributes[$name]);
	}
	
        /**
         * Gets a single value of a given attribute. If there are multiple, the first one is retrieved.
         * @param string $name attribute name.
         * @return null|string Value if found.
         */
	public function GetSingleAttribute($name)
	{
            if (!$this->HasAttribute($name)) 
            {
                return null;
            }
            return $this->proplist[$this->FindAttribute($name)[0]]['value'];
	}
	
        
        /**
         * Assigns a single value to a specific attribute. Fails if the attribute has multiple values.
         * @param string $name Attribute name to assign.
         * @param scalar $value Value to assign.
         * @param bool $nocreate If set to true, the method will also fail if the attribute doesn't exist yet.
         * @return bool True if the attribute has been set/created, false otherwise.
         */
	public function SetSingleAttribute($name,$value,$nocreate=false)
	{
		//$this->proplist[$name]=$value;
		$props=$this->FindAttribute($name);
		if(count($props)==0 && !$nocreate)
		{
			$this->AddAttribute($name,$value);
			return true;
		}
		if(count($props)!=1)
		{
			return false;
		}
		$this->proplist[$props[0]]['value']=$value;
                $this->dirty = true;
		return true;
	}
	/**
         * Removes a specific attribute from the object, optionally only if matching a value (other than false)
         * @param string $name Attribute name to remove.
         * @param mixed $value If set to anything other than boolean false, only attribute values matching this value will be removed.
         */
        public function EraseAttribute($name,$value=false)
        {
            // find entries matching the attribute name
            $props=$this->FindAttribute($name);
            // go through each one and remove
            foreach($props as $prop)
            {
                // keep attributes matching the given value if set
                if(($value!==false) && $value!==$this->proplist[$prop]['value'])
                {
                    continue;
                }
                // add the assignment entry ID to the erase list and set dirty bit
                $entryId=$this->proplist[$prop]['pid'];
                $entryValue=$this->proplist[$prop]['value'];
                $this->eraselist[]= $entryId;
                $this->dirty = true;
                // remove from the flat proplist
                unset($this->proplist[$prop]);
                // remove from the attributes hashmap
                if(isset($this->attributes[$name]))
                {
                    // if it has multiple values, find the correct one to erase
                    if(is_array($this->attributes[$name]))
                    {
                        // find one value matching the one found in $proplist - guaranteed to exist since we're here
                        $indices=array_keys($this->attributes[$name],$entryValue);
                        // nuke it
                        unset($this->attributes[$name][$indices[0]]);
                        // if that was the last value, remove the whole array
                        if(count($this->attributes[$name])===0)
                        {
                            unset($this->attributes[$name]);
                        }
                        // otherwise renumber the array to close the hole
                        else
                        {
                            $this->attributes[$name] = array_values($this->attributes[$name]);
                        }
                    }
                    // if just one value, nuke it
                    else
                    {
                        unset($this->attributes[$name]);
                    }
                }
            }
            // renumber the proplist to close the holes
            $this->proplist=array_values($this->proplist);
        }
        
        
        /**
         * Explicitly appends a volatile attribute
         * @param type $name
         * @param type $value
         */
	public function AddAttribute($name,$value)
	{
		$propertyId=self::GetPropertyId($name);
		$attr= Array(
                    'name'=>$name,
                    'value'=>$value,
                    'pid'=>-1,
                    'propertyId'=>$propertyId
		);
		$this->proplist[]=$attr;
	}
        
	/**
         * Commits the changes to the live object to the database
         */
	public function Save()
	{
            // updates and additions first
            for($i=0;$i<count($this->proplist);$i++)
            {
                $id=(int)$this->proplist[$i]['pid'];
                $value=$this->proplist[$i]['value'];
                // an ID of "-1" indicates there's no entry for this assignment yet, create it
                if($id==-1)
                {
                    $id=EVA::AppendProperty($this->id,$this->proplist[$i]['propertyId'],$value);
                    // reflect the change in the object
                    $this->proplist[$i]['pid']=$id;
                }
                // update the existing assignment directly by ID
                else
                {
                    EVA::UpdateProperty($id,$value);
                }
            }
            // perform erases on the list, if any
            foreach($this->eraselist as $entryId)
            {                
                self::DeleteAttribute($entryId);
            }
            // clear the erase list
            $this->eraselist = [];
            // clear the dirty bit
            $this->dirty = false;
	}
}