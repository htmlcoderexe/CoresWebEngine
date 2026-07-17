<?php
/**
 * Represents a ticket assignment group, linked to a user group.
 *
 */
class TicketGroup
{
    /**
     * Creates an instance of TicketGroup
     * @param int $id Group ID
     * @param string $name Group name
     * @param string $description Group description
     * @param int $func_group User Group ID linked to the ticket group
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $description,
        public int $func_group
    ){}
    
    public const TABLE = "ticket_groups";
    public const FIELDS = ['id',
        'name','description','func_group'
        ];
    public const SCHEMA = [
        'name'=>'VARCHAR(200)','description'=>'TEXT','func_group'=>'INT'
        ];
    
    /**
     * Creates an instance from a database row
     * @param array $row An associative array containing the instance data
     * @return TicketGroup|null
     */
    public static function FromRow(array $row) : TicketGroup | null
    {
        $group = new TicketGroup(id: $row['id'], name: $row['name'], description: $row['description'], func_group: $row['func_group']);
        return $group;
    }
    
    /**
     * Loads a TicketGroup by its ID
     * @param int $id Group ID
     * @return TicketGroup|null
     */
    public static function Load(int $id) : TicketGroup | null
    {
        $row = DBHelper::GetRowById(table: self::TABLE, id: $id, fields: self::FIELDS);
        if(!$row)
        {
            return null;
        }
        $group = self::FromRow($row);
        return $group;
    }
    
    /**
     * Writes the group's current state to database
     */
    public function Update()
    {
        $row = ['name'=>$this->name, 'description'=>$this->description, 'func_group'=>$this->func_group];
        DBHelper::Update(table: self::TABLE, assignments: $row, where: ['id'=>$this->id]);
    }
    
    /**
     * Crates a new group
     * @param string $name Group name
     * @param string $description Group description
     * @param int $func_group User group linked to the ticket group
     * @return TicketGroup
     */
    public static function Create(string $name, string $description, int $func_group) : TicketGroup
    {
        $row = [null, $name, $description, $func_group];
        DBHelper::Insert(table: self::TABLE, values:  $row);
        $id = DBHelper::GetLastId();
        $group = new TicketGroup(id: $id, name: $name, description: $description, func_group: $func_group);
        return $group;
    }
    
    /**
     * Retrieves all ticket groups, also containing information on ticket count
     * @param bool $openonly If true, only counts active tickets and skips groups containing none.
     * @return array An array of associative arrays matching field names. The 'id' field is renamed to 'gid' and there is an additional 'ticketcount' field. 
     */
    public static function GetAllGroups(bool $openonly = false) : array
    {
        //$groups = DBHelper::GetAllRows(table: self::TABLE, fields: self::FIELDS);
        $where = "";
        $tg = TicketGroup::TABLE;
        $tt = TicketInfo::TABLE;
        if($openonly)
        {
            $where = "WHERE $tt.last_status <> 6 ";
        }
        $qc = "SELECT agroup, COUNT(*) FROM " . TicketInfo::TABLE . " GROUP BY agroup";
        $qc = "SELECT $tg.id as gid, $tg.name as name, COUNT($tt.id) as ticketcount, $tg.func_group as func_group, $tg.description as description "
                . "FROM $tg LEFT JOIN $tt "
                . "ON $tg.id = $tt.agroup "
                . $where
                . "GROUP BY $tg.id";
        $groups = DBHelper::RunTable($qc, []);
        return $groups;
    }
}
