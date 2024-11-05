<?php
class EVA
{
	public $id;
	public $type;
	public $attributes;
	public $proplist;
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
	
	public static function CreateObject($type,$owner = 0,$blueprint=Array())
	{
            if($owner === -1)
            {
                $currentuid = User::GetCurrentUser()->userid;
                if(User::GetCurrentUser()->IsGuest())
                {
                    $currentuid=0;
                }
                $owner = $currentuid;
            }
            
            DBHelper::Insert('eva_objects',Array(null,$type,$owner));
            $objid=DBHelper::GetLastId();
            $object= new EVA($objid);
            while(count($blueprint)>0)
            {
                $prop=array_shift($blueprint);
                $object->AddAttribute($prop,'');
            }
            return $object;
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
	
        
        public static function GetPropertyId($propname)
	{
		$query="SELECT id
		FROM eva_properties
		WHERE name=?";
		return DBHelper::RunScalar($query, [$propname]);
	}
        
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
                    self::CreateProperty($objid, $propid, $value);
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
	
	public static function CreateProperty($objId, $id,$value)
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
	
	public function FindAttribute($name)
	{
		return array_keys(array_column($this->proplist,'name'),$name);
	}
	
	public function HasAttribute($name)
	{
		$result=$this->FindAttribute($name);
		Utility::ddump($result);
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
	
	public function AddAttribute($name,$value)
	{
                $stmt = DBHelper::$DBLink->prepare("SELECT id FROM eva_properties WHERE name =?");
                $stmt->bindParam(1, $name);
		$propertyId=DBHelper::GetList($stmt)[0];
		$attr= Array(
                    'name'=>$name,
                    'value'=>$value,
                    'pid'=>-1,
                    'propertyId'=>$propertyId
		);
		$this->proplist[]=$attr;
	}
	
	public function Save()
	{
		for($i=0;$i<count($this->proplist);$i++)
		{
			$id=(int)$this->proplist[$i]['pid'];
			$value=$this->proplist[$i]['value'];
			if($id==-1)
			{
				
				$id=EVA::CreateProperty($this->id,$this->proplist[$i]['propertyId'],$value);
				$this->proplist[$i]['pid']=$id;
			}
			else
			{
				EVA::UpdateProperty($id,$value);
			}
		}
	}
}