<?php

namespace Models\Software;

use Common\DBHelper;


/**
 * Represents a software publisher or developer.
 */
class Publisher
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
	 * Creates an instance of Publisher from associative array (for example, database row)
	 * @param array $row Database row or other associative array
	 * @return Publisher|null The object created from the row.
	 */
	public static function FromRow(array $row) : Publisher | null
	{
		$obj = new Publisher(
			id: $row['id'],
			name: $row['name'],
			description: $row['description'],
			icon: $row['icon']
		);
		return $obj;
	}
	
	/**
	 * Loads a specific Publisher by ID
	 * @param int $id ID to be loaded.
	 * @returns Publisher|null The Publisher instance if found
	 */
	public static function Load(int $id) : Publisher | null
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
	 * Creates a new Publisher object and saves it to the database.
	 * @param string $name Publisher name
	 * @param string $description Publisher description
	 * @param string $icon Image blobid representing the publisher
	 * @returns Publisher|null The newly created object, if successful.
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
		$obj = new Publisher(
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
