<?php

/**
 * Represents a software publisher or developer.
 */
class SoftwarePublisher
{
	/**
	 * Creates an instance of the object.
	 * @param int $id ID of the object
	 * @param string $name Publisher name
	 * @param string $description Publisher description
	 * @param string $icon Image blobid representing the publisher
	 */
	public function __construct(
		public int $id,
		public string $name,
		public string $description,
		public string $icon
	){}
	
	public const TABLE = 'software_publishers';
	
	public const SCHEMA = [
		'name'=>'VARCHAR(200)',
		'description'=>'TEXT',
		'icon'=>'VARCHAR(100)'
	];
	
	public const FIELDS = ['id',
		'name',
		'description',
		'icon'
	];
	
	/**
	 * Creates an instance of SoftwarePublisher from associative array (for example, database row)
	 * @param array $row Database row or other associative array
	 * @return SoftwarePublisher|null The object created from the row.
	 */
	public static function FromRow(array $row) : SoftwarePublisher | null
	{
		$obj = new SoftwarePublisher(
			id: $row['id'],
			name: $row['name'],
			description: $row['description'],
			icon: $row['icon']
		);
		return $obj;
	}
	
	/**
	 * Loads a specific SoftwarePublisher by ID
	 * @param int $id ID to be loaded.
	 * @returns SoftwarePublisher|null The SoftwarePublisher instance if found
	 */
	public static function Load(int $id) : SoftwarePublisher | null
	{
		$row = DBHelper::GetRowById(table: self::TABLE, id: $id, fields: self::FIELDS);
		if(!$row)
		{
			return null;
		}
		$obj = self::FromRow($row);
		return $obj;
	}
	
	/**
	* Saves the state of the object to the database.
	*/
	public function Update()
	{
		$update = [
			'name'=>$this->name,
			'description'=>$this->description,
			'icon'=>$this->icon
		];
		DBHelper::Update(table: self::TABLE, where: ['id'=>$this->id], assignments: $update);
	}
	
	/**
	 * Creates a new SoftwarePublisher object and saves it to the database.
	 * @param string $name Publisher name
	 * @param string $description Publisher description
	 * @param string $icon Image blobid representing the publisher
	 * @returns SoftwarePublisher|null The newly created object, if successful.
	 */
	public static function Create(
		string $name,
		string $description,
		string $icon
	)
	{
		$row = [null,$name, $description, $icon];
		DBHelper::Insert(table: self::TABLE, values: $row);
		$id = DBHelper::GetLastId();
		$obj = new SoftwarePublisher(
			id: $id,
			name: $name,
			description: $description,
			icon: $icon
		);
		return $obj;
	}
        public static function GetList()
        {
            $result = [];
            $filters = [];
            $p = [];
            $where = [];
            $q= DBHelper::Select(table: self::TABLE, fields: self::FIELDS, where: $where);
            $result = DBHelper::RunTable($q, $p);
            return $result;

        }
}