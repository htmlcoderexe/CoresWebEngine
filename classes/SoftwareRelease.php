<?php

/**
 * Describes a downloadable collection of files for a specific release
 */
class SoftwareRelease
{
	/**
	 * Creates an instance of the object.
	 * @param int $id ID of the object
	 * @param int $software_id Software Package ID
	 * @param string $version elease version
	 * @param string $description Description, changelog or other information
	 * @param int $type Release type
	 * @param int $time Release date
	 * @param array $files Files attached to the release, as array of SoftwareReleaseFile
	 */
	public function __construct(
		public int $id,
		public int $software_id,
		public string $version,
		public string $description,
		public int $type,
		public int $time,
        	public array $files
	){}
	
	public const TABLE = 'software_releases';
	
	public const SCHEMA = [
		'software_id'=>'INT',
		'version'=>'VARCHAR(100)',
		'description'=>'TEXT',
		'type'=>'INT',
		'time'=>'INT'
	];
	
	public const FIELDS = ['id',
		'software_id',
		'version',
		'description',
		'type',
		'time'
	];
	
	/**
	 * Creates an instance of SoftwareRelease from associative array (for example, database row)
	 * @param array $row Database row or other associative array
	 * @param array $files Files attached to the release, as array of SoftwareReleaseFile
	 * @return SoftwareRelease|null The object created from the row.
	 */
	public static function FromRow(array $row, array $files = []) : SoftwareRelease | null
	{
		$obj = new SoftwareRelease(
			id: $row['id'],
			software_id: $row['software_id'],
			version: $row['version'],
			description: $row['description'],
			type: $row['type'],
			time: $row['time'],
			files: $files
		);
		return $obj;
	}
	
	/**
	 * Loads a specific SoftwareRelease by ID
	 * @param int $id ID to be loaded.
	 * @returns SoftwareRelease|null The SoftwareRelease instance if found
	 */
	public static function Load(int $id) : SoftwareRelease | null
	{
		$row = DBHelper::GetRowById(table: self::TABLE, id: $id, fields: self::FIELDS);
		if(!$row)
		{
			return null;
		}
                $files = SoftwareReleaseFile::GetFiles(id: $id);
		$obj = self::FromRow($row, $files);
		return $obj;
	}
	
	/**
	* Saves the state of the object to the database.
	*/
	public function Update()
	{
		$update = [
			'software_id'=>$this->software_id,
			'version'=>$this->version,
			'description'=>$this->description,
			'type'=>$this->type,
			'time'=>$this->time
		];
		DBHelper::Update(table: self::TABLE, where: ['id'=>$this->id], assignments: $update);
	}
	
	/**
	 * Creates a new SoftwareRelease object and saves it to the database.
	 * @param int $software_id Software Package ID
	 * @param string $version elease version
	 * @param string $description Description, changelog or other information
	 * @param int $type Release type
	 * @param int $time Release date
	 * @returns SoftwareRelease|null The newly created object, if successful.
	 */
	public static function Create(
		int $software_id,
		string $version,
		string $description,
		int $type,
		int $time
	)
	{
		$row = [null,$software_id, $version, $description, $type, $time];
		DBHelper::Insert(table: self::TABLE, values: $row);
		$id = DBHelper::GetLastId();
		$obj = new SoftwareRelease(
			id: $id,
			software_id: $software_id,
			version: $version,
			description: $description,
			type: $type,
			time: $time
		);
		return $obj;
	}
        
        /**
         * Gets all releases belonging to a specific release ID
         * @param int $id Software release ID
         */
        public static function GetReleases(int $id) : array
        {
            $rows = DBHelper::GetRowsByField(table:self::TABLE, field:'software_id', value:$id, fields:self::FIELDS);
            if(!$rows)
            {
                return [];
            }
            $releases = [];
            foreach($rows as $row)
            {
                $releases[]= self::FromRow($row);
            }
            return $releases;
        }
}
