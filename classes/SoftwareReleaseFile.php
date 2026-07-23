<?php

/**
 * 
 */
class SoftwareReleaseFile
{
	/**
	 * Creates an instance of the object.
	 * @param int $id ID of the object
	 * @param int $release_id ID of the software release containing the file
	 * @param string $blobid File ID
	 * @param string $comment Any additional information with the file
	 */
	public function __construct(
		public int $id,
		public int $release_id,
		public string $blobid,
		public string $comment
	){}
	
	public const TABLE = 'software_release_files';
	
	public const SCHEMA = [
		'release_id'=>'INT',
		'blobid'=>'VARCHAR(100)',
		'comment'=>'TEXT'
	];
	
	public const FIELDS = ['id',
		'release_id',
		'blobid',
		'comment'
	];
	
	/**
	 * Creates an instance of SoftwareReleaseFile from associative array (for example, database row)
	 * @param array $row Database row or other associative array
	 * @return SoftwareReleaseFile|null The object created from the row.
	 */
	public static function FromRow(array $row) : SoftwareReleaseFile | null
	{
		$obj = new SoftwareReleaseFile(
			id: $row['id'],
			release_id: $row['release_id'],
			blobid: $row['blobid'],
			comment: $row['comment']
		);
		return $obj;
	}
	
	/**
	 * Loads a specific SoftwareReleaseFile by ID
	 * @param int $id ID to be loaded.
	 * @returns SoftwareReleaseFile|null The SoftwareReleaseFile instance if found
	 */
	public static function Load(int $id) : SoftwareReleaseFile | null
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
			'release_id'=>$this->release_id,
			'blobid'=>$this->blobid,
			'comment'=>$this->comment
		];
		DBHelper::Update(table: self::TABLE, where: ['id'=>$this->id], assignments: $update);
	}
	
	/**
	 * Creates a new SoftwareReleaseFile object and saves it to the database.
	 * @param int $release_id ID of the software release containing the file
	 * @param string $blobid File ID
	 * @param string $comment Any additional information with the file
	 * @returns SoftwareReleaseFile|null The newly created object, if successful.
	 */
	public static function Create(
		int $release_id,
		string $blobid,
		string $comment
	)
	{
		$row = [null,$release_id, $blobid, $comment];
		DBHelper::Insert(table: self::TABLE, values: $row);
		$id = DBHelper::GetLastId();
		$obj = new SoftwareReleaseFile(
			id: $id,
			release_id: $release_id,
			blobid: $blobid,
			comment: $comment
		);
		return $obj;
	}
        
        /**
         * Gets files belonging to this release
         * @param int $id Release ID
         */
        public static function GetFiles(int $id) : array
        {
            $rows = DBHelper::GetRowsByField(table:self::TABLE, field:'release_id', value:$id, fields:self::FIELDS);
            if(!$rows)
            {
                return [];
            }
            $files = [];
            foreach($rows as $row)
            {
                $files[]= self::FromRow($row);
            }
            return $files;
        }
}
