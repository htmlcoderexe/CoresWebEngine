<?php
//Module::DemandProperty("owner","Owner","User owning this object.");
//Module::DemandProperty("user_group","User group","User group linked to this object.");
/**
 * Description of TicketGroup
 *
 * @author admin
 */
class TicketGroup
{
    public function __construct(
    public int $id,
    public string $name,
    public string $description,
    public int $func_group){}
    
    public const TABLE = "ticket_groups";
    public const FIELDS = ['id',
        'name','description','func_group'
        ];
    public const SCHEMA = [
        'name'=>'VARCHAR(200)','description'=>'TEXT','func_group'=>'INT'
        ];
    
    public static function FromRow(array $row) : TicketGroup | null
    {
        $group = new TicketGroup(id: $row['id'], name: $row['name'], description: $row['description'], func_group: $row['func_group']);
        return $group;
    }
    
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
    public function Update()
    {
        $row = ['name'=>$this->name, 'description'=>$this->description, 'func_group'=>$this->func_group];
        DBHelper::Update(table: self::TABLE, assignments: $row, where: ['id'=>$this->id]);
    }
    
    public static function Create(string $name, string $description, int $func_group) : TicketGroup
    {
        $row = [null, $name, $description, $func_group];
        DBHelper::Insert(table: self::TABLE, values:  $row);
        $id = DBHelper::GetLastId();
        $group = new TicketGroup(id: $id, name: $name, description: $description, func_group: $func_group);
        return $group;
    }
    
    public static function GetAllGroups(bool $openonly = false)
    {
        //$groups = DBHelper::GetAllRows(table: self::TABLE, fields: self::FIELDS);
        $where = "";
        if($openonly)
        {
            $where = "WHERE $tt.last_status <> 6 ";
        }
        $qc = "SELECT agroup, COUNT(*) FROM " . TicketInfo::TABLE . " GROUP BY agroup";
        $tg = TicketGroup::TABLE;
        $tt = TicketInfo::TABLE;
        $qc = "SELECT $tg.id as gid, $tg.name as name, COUNT($tt.id) as ticketcount, $tg.func_group as func_group, $tg.description as description "
                . "FROM $tg LEFT JOIN $tt "
                . "ON $tg.id = $tt.agroup "
                . $where
                . "GROUP BY $tg.id";
        $groups = DBHelper::RunTable($qc, []);
        for($i=0;$i<count($groups);$i++)
        {
            
        }
        return $groups;
    }
    
    
    public static function FromName($name)
    {
        $candidates = EVA::GetByProperty("name", $name, "ticket_group");
        if(count($candidates) > 0)
        {
            return new TicketGroup($candidates[0]);
        }
        return;
        
    }
    
    public function EVA__construct($id)
    {
        $obj = new EVA($id);
        if($obj->id < 1)
        {
            return;
        }
        $this->name = $obj->attributes['name'];
        $this->description = $obj->attributes['description'];
        $this->EVAobj = $obj;
        $this->func_group = $obj->attributes['user_group'];
        $this->id = $id;
    }
}
