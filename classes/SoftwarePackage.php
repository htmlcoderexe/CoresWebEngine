<?php

/**
 * Represents a piece of software
 *
 */
class SoftwarePackage
{
    /**
     * Creates an instance of the package
     * @param int $id Package ID
     * @param string $title Package title
     * @param string $description Package description
     * @param string $icon An blobid of an icon used to represent the software 
     * @param int $category Software category
     * @param int $publisher Publisher or developer
     * @param int $type Software distribution type
     * @param int $owner User owning the package
     * @param int $gid User group owning the package
     * @param array $releases Array of releases (various versions and platforms of the software)
     * @param array $screenshots Array of Screenshot objects containing the screenshots
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public string $icon,
        public int $category,
        public int $publisher,
        public int $type,
        public int $owner,
        public int $gid,
        public array $releases,
        public array $screenshots
    ){}
    public const TABLE = 'software';
    public const FIELDS = ['id',
        'title',
        'description',
        'icon',
        'category',
        'publisher',
        'type',
        'owner',
        'gid'
    ];
    public const SCHEMA = [
        'title'=>'VARCHAR(200)',
        'description'=>'TEXT',
        'icon'=>'VARCHAR(100)',
        'category'=>'INT',
        'publisher'=>'INT',
        'type'=>'INT',
        'owner'=>'INT',
        'gid'=>'INT'
    ];
    public static function FromRow(array $row, array $releases, array $screenshots) : SoftwarePackage | null
    {
        $package = new SoftwarePackage(
            id: $row['id'],
            title: $row['title'],
            description: $row['description'],
            icon: $row['icon'],
            category: $row['category'],
            publisher: $row['publisher'],
            type: $row['type'],
            owner: $row['owner'],
            gid: $row['gid'],
            releases: $releases,
            screenshots: $screenshots
        );
        return $package;
    }
    public static function Load(int $id) : SoftwarePackage | null
    {
        $row = DBHelper::GetRowById(table: self::TABLE, id: $id, fields: self::FIELDS);
        if(!$row)
        {
            return null;
        }

        $package = self::FromRow(row: $row, releases: $releases, screenshots: $screenshots);
        
        return $package;
    }
}
