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
     
        public const TABLE ="user_info";
        public const FIELDS = [
            'uid',
            'firsname',
            'lastname',
            'nickname',
            'description',
            'avatar',
            'email'];
        public const SCHEMA = [
            'uid'=>'INT',
            'firsname'=>'VARCHAR(200)',
            'lastname'=>'VARCHAR(200)',
            'nickname'=>'VARCHAR(200)',
            'description'=>'TEXT',
            'avatar'=>'VARCHAR(100)',
            'email'=>'VARCHAR(200)'];
        
	public static function GetOneProperty($user,$propname)
	{
            $uid = (int)$user->userid;
            $inforow = DBHelper::GetRowsByField(table: self::TABLE, field:'uid', value:$uid, fields:self::FIELDS);
            if($inforow)
            {
                if(isset($inforow[0][$propname]))
                {
                    return $inforow[0][$propname];
                }
            }
            return null;
       }
	
	public static function SetOneProperty($user,$propname,$propvalue)
	{
            if($propname!='uid' && in_array($propname, self::FIELDS, true))
            {
                DBHelper::Update(table:self::TABLE, assignments: [$propname=>$propvalue],where: ['uid'=>$user->userid]);
            }
	}
        
        public static function Create(int $uid, string $nickname, string $firstname = "", string $lastname ="", string $description = "", string $avatar ="", string $email = "")
        {
            $update = [$uid, $firstname, $lastname, $nickname, $description, $avatar, $email];
            DBHelper::Insert(table: self::TABLE, values: $update);
        }


        

	
	
	
}