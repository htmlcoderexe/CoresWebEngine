<?php

/**
 * Represents a piece of software
 */
class SoftwarePackage
{
	/**
	 * Creates an instance of the object.
	 * @param int $id ID of the object
	 * @param string $title Package title
	 * @param string $description Package description
	 * @param string $icon A blobid of an icon used to represent the software
	 * @param int $screenshot_album An ID referring to a PictureSet containing screenshots
	 * @param int $category Software caterory
	 * @param int $publisher Publisher or developer
	 * @param int $type Software distribution type
	 * @param int $uid User owning the package
	 * @param int $gid User group owning the package
         * @param array $releases Releases that are part of this package
	 */
	public function __construct(
		public int $id,
		public string $title,
		public string $description,
		public string $icon,
		public int $screenshot_album,
		public int $category,
		public int $publisher,
		public int $type,
		public int $uid,
		public int $gid,
                public array $releases
	){}
	
	public const TABLE = 'software';
	
	public const SCHEMA = [
		'title'=>'VARCHAR(200)',
		'description'=>'TEXT',
		'icon'=>'VARCHAR(100)',
		'screenshot_album'=>'INT',
		'category'=>'INT',
		'publisher'=>'INT',
		'type'=>'INT',
		'uid'=>'INT',
		'gid'=>'INT'
	];
	
	public const FIELDS = ['id',
		'title',
		'description',
		'icon',
		'screenshot_album',
		'category',
		'publisher',
		'type',
		'uid',
		'gid'
	];
	
	/**
	 * Creates an instance of SoftwarePackage from associative array (for example, database row)
	 * @param array $row Database row or other associative array
         * @param array $releases Array of SoftwareReleases belonging to this package.
	 * @return SoftwarePackage|null The object created from the row.
	 */
	public static function FromRow(array $row, array $releases) : SoftwarePackage | null
	{
		$obj = new SoftwarePackage(
			id: $row['id'],
			title: $row['title'],
			description: $row['description'],
			icon: $row['icon'],
			screenshot_album: $row['screenshot_album'],
			category: $row['category'],
			publisher: $row['publisher'],
			type: $row['type'],
			uid: $row['uid'],
			gid: $row['gid'],
                        releases: $releases
		);
		return $obj;
	}
	
	/**
	 * Loads a specific SoftwarePackage by ID
	 * @param int $id ID to be loaded.
	 * @returns SoftwarePackage|null The SoftwarePackage instance if found
	 */
	public static function Load(int $id) : SoftwarePackage | null
	{
		$row = DBHelper::GetRowById(table: self::TABLE, id: $id, fields: self::FIELDS);
		if(!$row)
		{
			return null;
		}
                $releases = SoftwareRelease::GetReleases(id: $id);
		$obj = self::FromRow($row, $releases);
		return $obj;
	}
	
	/**
	* Saves the state of the object to the database.
	*/
	public function Update()
	{
		$update = [
			'title'=>$this->title,
			'description'=>$this->description,
			'icon'=>$this->icon,
			'screenshot_album'=>$this->screenshot_album,
			'category'=>$this->category,
			'publisher'=>$this->publisher,
			'type'=>$this->type,
			'uid'=>$this->uid,
			'gid'=>$this->gid
		];
		DBHelper::Update(table: self::TABLE, where: ['id'=>$this->id], assignments: $update);
	}
	
	/**
	 * Creates a new SoftwarePackage object and saves it to the database.
	 * @param string $title Package title
	 * @param string $description Package description
	 * @param string $icon A blobid of an icon used to represent the software
	 * @param int $screenshot_album An ID referring to a PictureSet containing screenshots
	 * @param int $category Software caterory
	 * @param int $publisher Publisher or developer
	 * @param int $type Software distribution type
	 * @param int $uid User owning the package
	 * @param int $gid User group owning the package
	 * @returns SoftwarePackage|null The newly created object, if successful.
	 */
	public static function Create(
		string $title,
		string $description,
		string $icon,
		int $screenshot_album,
		int $category,
		int $publisher,
		int $type,
		int $uid,
		int $gid
	)
	{
		$row = [null,$title, $description, $icon, $screenshot_album, $category, $publisher, $type, $uid, $gid];
		DBHelper::Insert(table: self::TABLE, values: $row);
		$id = DBHelper::GetLastId();
		$obj = new SoftwarePackage(
			id: $id,
			title: $title,
			description: $description,
			icon: $icon,
			screenshot_album: $screenshot_album,
			category: $category,
			publisher: $publisher,
			type: $type,
			uid: $uid,
			gid: $gid,
                        releases: []
                );
		return $obj;
	}
}
