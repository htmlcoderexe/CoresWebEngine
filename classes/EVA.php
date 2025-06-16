<?php
class EVA
{
	public $id;
	public $type;
	public $attributes;
	public $proplist;
        
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
	}
	
        /**
         * Creates a new EVA object of a specified type
         * @param string $type Object typename
         * @param int $owner Sets the owner of the created object. -1 uses current uid and 0 uses nobody.
         * @param array $blueprint List of attributes to preload on the object.
         * @return \EVA
         */
	public static function CreateObject($type,$owner = self::OWNER_NOBODY,$blueprint=[])
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
            return $object;
	}
        
        /**
         * Creates a new EVA property
         * @param type $name
         * @param type $dname
         * @param type $desc
         */
        public static function CreateProperty($name,$dname,$desc)
        {
            
            DBHelper::Insert('eva_properties',[null,$name,$dname,$desc]);
        }
        
	
        /**
         * Find a property ID
         * @param string $propname
         * @return int|bool ID of the property if it exists or false otherwise
         */
        public static function GetPropertyId($propname)
	{
		$query="SELECT id
		FROM eva_properties
		WHERE name=?";
		return DBHelper::RunScalar($query, [$propname]);
	}
        
	//gets
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
                return null;
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
         * Add a property assigned to a specific EVA object
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
        
        
        public static function DeleteAttribute($entryId)
        {
            DBHelper::Delete("eva_property_values",['id'=>$entryId]);
        }
        
	
        /**
         * Get EVA objects that have a specific property set to a specific value, filtered by object type.
         * @param string $property Name of the target property
         * @param string $value Value to be searched for
         * @param string $type Object type to filter by
         * @return array of matching EVA IDs if any found
         */
	public static function GetByProperty($property,$value,$type)
	{
            $query ="
		SELECT DISTINCT object_id FROM eva_property_values
		INNER JOIN eva_objects
		ON eva_objects.id = object_id
		INNER JOIN eva_properties 
		ON eva_properties.id =property_id
		WHERE value =? and eva_objects.type=? and eva_properties.name=?
		
		";
            return DBHelper::RunList($query,[$value,$type,$property]);
	}
	public static function GetKVA($property,$type)
	{
            $query ="
		SELECT DISTINCT object_id,value FROM eva_property_values
		INNER JOIN eva_objects
		ON eva_objects.id = object_id
		INNER JOIN eva_properties 
		ON eva_properties.id =property_id
		WHERE eva_objects.type=? and eva_properties.name=?
		
		";
            return DBHelper::RunTable($query,[$type,$property]);
	}
        
        
        public static function GetAllOfType($type)
        {
            $q=DBHelper::Select("eva_objects",["id"],["type"=>$type]);
            return DBHelper::RunList($q,[$type]);
        }
        
	
	public function FindAttribute($name)
	{
		return array_keys(array_column($this->proplist,'name'),$name);
	}
	
	public function HasAttribute($name)
	{
		$result=$this->FindAttribute($name);
		EngineCore::Dump2Debug($result);
		return count($result)>0;
		//return isset($this->attributes[$name]);
	}
	
	public function HasMultiple($name)
	{
		return is_array($this->attributes[$name]);
	}
	
	public function GetAttribute($name)
	{
		return isset($this->attributes[$name]);
	}
	
	public function GetSingleAttribute($name)
	{
            if (!$this->HasAttribute($name)) 
            {
                return null;
            }
            return $this->proplist[$this->FindAttribute($name)[0]]['value'];
	}
	
        
        
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
		return true;
	}
	
        public function EraseAttribute($name,$value=false)
        {
            $props=$this->FindAttribute($name);
            foreach($props as $prop)
            {
                if(($value!==false) && $value!==$this->proplist[$prop]['value'])
                {
                    continue;
                }
                $entryId=$this->proplist[$prop]['pid'];
                //var_dump($this);
                self::DeleteAttribute($entryId);
                unset($this->proplist[$prop]);
                if(isset($this->attributes[$name]))
                {
                    if(is_array($this->attributes[$name]))
                    {
                        $indices=array_keys($this->attributes[$name],$value);
                        foreach($indices as $index)
                        {
                            unset($this->attributes[$name][$index]);
                        }
                        if(count($this->attributes[$name])===0)
                        {
                            unset($this->attributes[$name]);
                        }
                    }
                    else
                    {
                        unset($this->attributes[$name]);
                    }
                }
                //var_dump($this);
                //die();
            }
            $this->proplist=array_values($this->proplist);
        }
        
        
        /**
         * Explicitly append a volatile attribute
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
        
        
        public function GetChildren($child_type)
        {
            $q = DBHelper::Select("eva_mappings", ["child_id"], ["parent_type"=>$this->type,
                "child_type"=>$child_type,
                "parent_id"=>$this->id]);
            $child_list = DBHelper::GetList($q);
            return $child_list;
        }
        
	
	public function Save()
	{
		for($i=0;$i<count($this->proplist);$i++)
		{
			$id=(int)$this->proplist[$i]['pid'];
			$value=$this->proplist[$i]['value'];
			if($id==-1)
			{
				
				$id=EVA::AppendProperty($this->id,$this->proplist[$i]['propertyId'],$value);
				$this->proplist[$i]['pid']=$id;
			}
			else
			{
				EVA::UpdateProperty($id,$value);
			}
		}
	}
}