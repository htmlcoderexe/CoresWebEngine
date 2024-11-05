<?php

 class UserExtendedProps
{
        /*
	public static function GetPropertySet($propertysetname)
	{
		$propertysetname=mysql_real_escape_string($propertysetname);
		$setid=(int)DBHelper::GetList("SELECT id FROM user_property_sets WHERE property_set_name='$propertysetname'")[0];
		
		$request="SELECT user_property_id
		FROM user_property_set_map
		WHERE user_property_set_id=$setid";
		return DBHelper::GetList($request);
	}
	//*/
	public static function GetOneProperty($user,$propname)
	{
            return EVA::LoadPropFromDB((int)$user->userid,$propname)[0];
	}
	
	public static function SetOneProperty($user,$propname,$propvalue)
	{
		EVA::WritePropByName((int)$user->userid,$propname,$propvalue);
	}


        

	public static function GetPropertyId($propname)
	{
		return EVA::GetPropertyId($propname);
	}
	
	public static function UserAddProperty($user,$key,$value)
	{
		$uid=$user->userid;
		$propid=UserExtendedProps::GetPropertyId($key);
		EVA::CreateProperty($uid, $propid, $value);
	}
	
	
}