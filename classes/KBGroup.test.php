<?php

require_once "MiniTest.php";

class KBGroupTestBacker implements IKBGroupBacker
{
    public $groups;
    public function __construct($groups = [])
    {
        $this->groups = $groups;
    }
    public function GetItems($id)
    {
        if(array_key_exists($id,$this->groups))
        {
            return $this->groups[$id];
        }
        return [];
    }
    public function Find($id) : int
    {
        $ids=array_keys($this->groups);
        foreach($ids as $groupId)
        {
            foreach($this->groups[$groupId] as $item)
            {
                if($item['id'] == $id)
                {
                    return $groupId;
                }
            }
        }
        return 0;
    }
    public function SetItems($id,$items)
    {
        $this->groups[$id] = $items;
    }
}

class KBGroupTest extends MiniTest
{
    public $mockDB;
    public $realDB;
    public $testTableName = 'kbgroups_test';
    public const testdata1 = [
            1400=>[
                ['id'=>600,'prev'=>0,'next'=>700],
                ['id'=>700,'prev'=>600,'next'=>2900],
                ['id'=>2900,'prev'=>700,'next'=>0],
            ],
            1000=>[
                ['id'=>4100,'prev'=>0,'next'=>1200],
                ['id'=>1200,'prev'=>4100,'next'=>500],
                ['id'=>500,'prev'=>1200,'next'=>200],
                ['id'=>200,'prev'=>500,'next'=>0],
            ],
        ];
    public const testitems1 = [
            ['id'=>10400, 'prev'=> 0, 'next'=> 19900],
            ['id'=>19900, 'prev'=> 10400, 'next'=> 11100],
            ['id'=>11100, 'prev'=> 19900, 'next'=> 10000],
            ['id'=>10000, 'prev'=> 11100, 'next'=> 0],
    ];
    
    public function Reset()
    {
       
        
        $testTableName = $this->testTableName;
        $this->mockDB = new KBGroupTestBacker(self::testdata1);
        $q="DROP TABLE IF EXISTS `$testTableName`;
        CREATE TABLE IF NOT EXISTS `$testTableName` (
          `collectionId` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
          `ordinal` int NOT NULL,
          `entityId` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
          `prev` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `next` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        DBHelper::RunVoid($q, []);
        $this->realDB = new KBGroupDBBacker($testTableName);
        $testGroup14 = new KBGroup(backer: $this->realDB, items: self::testdata1[1400],id: 1400);
        $testGroup14->Save();
        $testGroup10 = new KBGroup(backer: $this->realDB, items: self::testdata1[1000],id: 1000);
        $testGroup10->Save();
        
    }
    
    public function Setup()
    {
        $this->Reset();
    }
    function GroupAddTests()
    {
        $this->tests[]=[
            'body'=> function(){
                $this->Reset();
                $testGroup14 = KBGroup::Load(backer:  $this->mockDB, id: 1400);
                $item300 = $testGroup14->Add(id: 30000);
                $testGroup14->Save();
                return [$item300,$testGroup14->items];
            },
            'expect'=>[[],[
                ['id'=>600,'prev'=>0,'next'=>700],
                ['id'=>700,'prev'=>600,'next'=>2900],
                ['id'=>2900,'prev'=>700,'next'=>30000],
                ['id'=>30000,'prev'=>2900,'next'=>0],]],
            'title'=>"Adding a new item to an existing group at the end",
            'group'=>"Add"
        ];
        $this->tests[]=[
            'body'=>function(){
                $testGroup14 = KBGroup::Load(backer:  $this->mockDB, id: 1400);
            
                $item302 = $testGroup14->Add(id: 30200, pos: 0);
                $testGroup14->Save();
                return $item302;
            },
            'expect'=>[['id'=>30200,'prev'=>0,'next'=>600],['id'=>600,'prev'=>30200,'next'=>700]],
            'title'=>"Adding a new item at the beginning of a group.",
            'group'=>"Add"
        ];
        $this->tests[]=[
            'body'=>function(){
               
            
                $item9001 = KBGroup::ProcessMove(backer: $this->mockDB,cg: 0, cp:0, cn:0, ng:0, np:0,nn:500,itemId: 9001);
                $testGroup10 = KBGroup::Load(backer:  $this->mockDB, id: 1000);
                return [$testGroup10->id, $testGroup10->items,$item9001];
            },
            'expect'=>[1000,
                [
                    ['id'=>4100,'prev'=>0,'next'=>1200],
                    ['id'=>1200,'prev'=>4100,'next'=>9001],
                    ['id'=>9001,'prev'=>1200,'next'=>500],
                    ['id'=>500,'prev'=>9001,'next'=>200],
                    ['id'=>200,'prev'=>500,'next'=>0],
                ],
                new KBGroupMoveResult(itemId: 9001, joinedGroup: 1000, nextItem: 500, previousItem: 1200, affectedItems: [
                    ['id'=>1200,'prev'=>4100,'next'=>9001],
                    ['id'=>9001,'prev'=>1200,'next'=>500],
                    ['id'=>500,'prev'=>9001,'next'=>200]
                    
                ])
            ],
            'title'=>"Adding a new item after an known item in unknown group",
            'group'=>"Add"
        ];
        $this->tests[]=[
            'body'=>function(){
               
            
                $testGroup14_before = KBGroup::Load(backer:  $this->mockDB, id: 1400);
                $item1234 = KBGroup::ProcessMove(backer: $this->mockDB,ng:1400,itemId: 1234);
                $testGroup14 = KBGroup::Load(backer:  $this->mockDB, id: 1400);
                return [$testGroup14_before->items,$item1234,$testGroup14->items];
            },
            'expect'=>[
                [
                    ['id'=>30200,'prev'=>0,'next'=>600],
                    ['id'=>600,'prev'=>30200,'next'=>700],
                    ['id'=>700,'prev'=>600,'next'=>2900],
                    ['id'=>2900,'prev'=>700,'next'=>30000],
                    ['id'=>30000,'prev'=>2900,'next'=>0]
                ],
                new KBGroupMoveResult(itemId: 1234, joinedGroup: 1400, previousItem: 30000),
                [
                    ['id'=>30200,'prev'=>0,'next'=>600],
                    ['id'=>600,'prev'=>30200,'next'=>700],
                    ['id'=>700,'prev'=>600,'next'=>2900],
                    ['id'=>2900,'prev'=>700,'next'=>30000],
                    ['id'=>30000,'prev'=>2900,'next'=>1234],
                    ['id'=>1234,'prev'=>30000,'next'=>0],
                ]
            ],
            'title'=>"ProcessMove to 1400 without args (specifying it should go to end).",
            'group'=>"Add"
        ];
    }
    function GroupRemoveTests()
    {
        
        $this->tests[]=[
            'title'=>"Removing two items from a group by ID",
            'group'=>"Remove",
            'body'=>function(){
                $this->Reset();
                $testGroup14 = KBGroup::Load(backer:  $this->mockDB, id: 1400);
                $testGroup14->Remove(id: 600);
                $testGroup14->Remove(id: 2900);
                return $testGroup14->items;
            },
            'expect'=>[['id'=>700,'prev'=>0,'next'=>0]]
        ];
    }
    public function __construct()
    {
        $this->GroupLoadSave();
        $this->GroupFindItem();
        $this->GroupAddTests();
        $this->GroupRemoveTests();
        $this->GroupMoveTests();
        //$this->GroupMoveTestsInDB();
    }
    
    
    public function GroupFindItem()
    {
        $this->tests[]=[
            'title'=>"Find groups of items by item ID",
            'group'=>"Find",
            'body'=>function(){
                $this->Reset();
                $id1400a = KBGroup::Find(backer: $this->mockDB, id: 700);
                $id1400b = KBGroup::Find(backer: $this->mockDB, id: 2900);
                $id1000 = KBGroup::Find(backer: $this->mockDB, id: 4100);
                $id0 = KBGroup::Find(backer: $this->mockDB, id: 541);
                return [$id1400a, $id1400b, $id1000, $id0];
            },
            'expect'=>[1400, 1400, 1000, 0]
        ]; 
    }
    
    public function GroupLoadSave()
    {
        $this->tests[]=[
            'title'=>"Load group by ID",
            'group'=>"Load/Save",
            'body'=>function(){
                $testGroup1 = KBGroup::Load(backer:  $this->mockDB, id: 1400);
                
                return [$testGroup1->id, $testGroup1->items];
            },
            'expect'=>[1400, 
                [
                    ['id'=>600,'prev'=>0,'next'=>700],
                    ['id'=>700,'prev'=>600,'next'=>2900],
                    ['id'=>2900,'prev'=>700,'next'=>0],
                ]
            ]
        ]; 
        $this->tests[]=[
            'title'=>"Save a group by ID, then retrieve it by the ID",
            'group'=>"Load/Save",
            'body'=>function(){
                $testGroup200 = new KBGroup(backer: $this->mockDB, items: self::testitems1, id: 20000);
                $testGroup200->Save();
                $testGroup200Again = KBGroup::Load(backer:  $this->mockDB, id: 20000);
                return [$testGroup200Again->id, $testGroup200Again->items];
            },
            'expect'=>[20000, 
                [
                    ['id'=>10400, 'prev'=> 0, 'next'=> 19900],
                    ['id'=>19900, 'prev'=> 10400, 'next'=> 11100],
                    ['id'=>11100, 'prev'=> 19900, 'next'=> 10000],
                    ['id'=>10000, 'prev'=> 11100, 'next'=> 0],
                ]
            ]
        ]; 
    }
    
    function GroupMoveTests()
    {
        $this->tests[]=[
            'title'=>"Move within group.",
            'group'=>"Move",
            'body'=>function(){
                $this->Reset();
                $testGroup14 = KBGroup::Load(backer:  $this->mockDB, id: 1400);
                $testGroup14->Move(2,0);
                $result = $testGroup14->items;
                $testGroup14->Save();
                return $result;
            },
            'expect'=>[
                ['id'=>2900,'prev'=>0,'next'=>600],
                ['id'=>600,'prev'=>2900,'next'=>700],
                ['id'=>700,'prev'=>600,'next'=>0],
            ]
        ];
        $this->tests[]=[
            'title'=>"Move 600 from 1400 to 1000, set after 1200",
            'group'=>"Move",
            'body'=>function(){
                $item6=KBGroup::ProcessMove(backer: $this->mockDB, itemId: 600,cp:0,cg:1400,cn:0,np:1200,ng:1000,nn:0);
                //ksort($item6);
                $testGroup10 = KBGroup::Load(backer: $this->mockDB, id: 1000);
                $testGroup14 = KBGroup::Load(backer: $this->mockDB, id: 1400);
                return [$item6,$testGroup14->items, $testGroup10->items];
            },
            'expect'=>[
                new KBGroupMoveResult(itemId: 600, joinedGroup: 1000, leftGroup: 1400, nextItem: 500, previousItem: 1200, affectedItems:[
                    ['id'=>2900,'prev'=>0,'next'=>700],
                    ['id'=>700,'prev'=>2900,'next'=>0],
                    ['id'=>1200,'prev'=>4100, 'next'=>600],
                    ['id'=>600,'prev'=>1200, 'next'=>500],
                    ['id'=>500,'prev'=>600, 'next'=>200]
                ]),
                [
                    ['id'=>2900,'prev'=>0,'next'=>700],
                    ['id'=>700,'prev'=>2900,'next'=>0],
                ],
                [
                    ['id'=>4100,'prev'=>0,'next'=>1200],
                    ['id'=>1200,'prev'=>4100,'next'=>600],
                    ['id'=>600,'prev'=>1200,'next'=>500],
                    ['id'=>500,'prev'=>600,'next'=>200],
                    ['id'=>200,'prev'=>500,'next'=>0],
                ]
            ]
        ];
        $this->tests[]=[
            'title'=>"Move 4100 from 1000 to 1400, set before 700",
            'group'=>"Move",
            'body'=>function(){
                $item1=KBGroup::ProcessMove(backer: $this->mockDB, itemId: 4100,cp:0,cg:1000,cn:0,np:0,ng:1400,nn:700);
                //ksort($item1);
                $testGroup10 = KBGroup::Load(backer: $this->mockDB, id: 1000);
                $testGroup14 = KBGroup::Load(backer: $this->mockDB, id: 1400);
                return [$item1,$testGroup14->items];
            },
            'expect'=>[
                    new KBGroupMoveResult(itemId:4100, joinedGroup: 1400, leftGroup: 1000, previousItem: 2900, nextItem: 700, affectedItems: [
                        ['id'=>1200, 'prev'=>0, 'next'=>600],
                        ['id'=>2900, 'prev'=>0, 'next'=>4100],
                        ['id'=>4100, 'prev'=>2900, 'next'=>700],
                        ['id'=>700, 'prev'=>4100, 'next'=>0]
                    ]),                
                    [
                        ['id'=>2900,'prev'=>0,'next'=>4100],
                        ['id'=>4100, 'prev'=>2900, 'next'=>700],
                        ['id'=>700,'prev'=>4100,'next'=>0]

                    ]
                ]
        ];
        $this->tests[]=[
            'title'=>"Move 4100 from 1400 to 1400, set before 700, again",
            'group'=>"Move",
            'body'=>function(){
                $item1=KBGroup::ProcessMove(backer: $this->mockDB, itemId: 4100,cp:0,cg:1400,cn:0,np:2900,ng:1400,nn:700);
                //ksort($item1);
                $testGroup10 = KBGroup::Load(backer: $this->mockDB, id: 1000);
                $testGroup14 = KBGroup::Load(backer: $this->mockDB, id: 1400);
                return [$item1, $testGroup14->items];
            },
            'expect'=>[new KBGroupMoveResult(noChange:true),                
                    [
                        ['id'=>2900,'prev'=>0,'next'=>4100],
                        ['id'=>4100, 'prev'=>2900, 'next'=>700],
                        ['id'=>700,'prev'=>4100,'next'=>0]

                    ]]
        ];
        $this->tests[]=[
            'title'=>"Move 200000 from nowhere to 205000, which doesn't exist yet",
            'group'=>"Move",
            'body'=>function(){
                $item1=KBGroup::ProcessMove(backer: $this->mockDB, itemId: 200000, cp:0,cg:0,cn:0,np:0,ng:205000,nn:0);
                $g2050=KBGroup::Load(backer:$this->mockDB, id: 205000);
                //ksort($item1);
                return [$item1,$g2050->items];
            },
            'expect'=>[new KBGroupMoveResult(itemId:200000, joinedGroup:205000, leftGroup:0, nextItem:0, previousItem:0),
                [['id'=>200000,'prev'=>0,'next'=>0]]
                ]
        ];
    }
    function XXNOTUSEDGroupMoveTestsInDB()
    {
        $this->tests[]=[
            'title'=>"Move within group.",
            'group'=>"Move With DB",
            'body'=>function(){
                $this->Reset();
                $testGroup14 = KBGroup::Load(backer:  $this->realDB, id: 1400);
                $testGroup14->Move(2,0);
                $result = $testGroup14->items;
                $testGroup14->Save();
                return $result;
            },
            'expect'=>[
                ['id'=>2900,'prev'=>0,'next'=>600],
                ['id'=>600,'prev'=>2900,'next'=>700],
                ['id'=>700,'prev'=>600,'next'=>0],
            ]
        ];
        $this->tests[]=[
            'title'=>"Move 600 from 1400 to 1000, set after 1200",
            'group'=>"Move With DB",
            'body'=>function(){
                $item6=KBGroup::ProcessMove(backer: $this->realDB, itemId: 600,cp:0,cg:1400,cn:0,np:1200,ng:1000,nn:0);
                //ksort($item6);
                $testGroup10 = KBGroup::Load(backer: $this->realDB, id: 1000);
                $testGroup14 = KBGroup::Load(backer: $this->realDB, id: 1400);
                return [$item6,$testGroup14->items];
            },
            'expect'=>[
                ['id'=>600,'joined'=>1000,'left'=>1400,'next'=>500,'prev'=>1200],
                [
                    ['id'=>2900,'prev'=>0,'next'=>700],
                    ['id'=>700,'prev'=>2900,'next'=>0],
                ]
            ]
        ];
        $this->tests[]=[
            'title'=>"Move 4100 from 1000 to 1400, set before 700",
            'group'=>"Move With DB",
            'body'=>function(){
                $item1=KBGroup::ProcessMove(backer: $this->realDB, itemId: 4100,cp:0,cg:1000,cn:0,np:0,ng:1400,nn:700);
                //ksort($item1);
                $testGroup10 = KBGroup::Load(backer: $this->realDB, id: 1000);
                $testGroup14 = KBGroup::Load(backer: $this->realDB, id: 1400);
                return $item1;
            },
            'expect'=>['id'=>4100,'joined'=>1400,'left'=>1000,'next'=>700,'prev'=>2900]
        ];
        $this->tests[]=[
            'title'=>"Move 200000 from nowhere to 205000, which doesn't exist yet",
            'group'=>"Move With DB",
            'body'=>function(){
                $item1=KBGroup::ProcessMove(backer: $this->realDB, itemId: 200000, cp:0,cg:0,cn:0,np:0,ng:205000,nn:0);
                //ksort($item1);
                return $item1;
            },
            'expect'=>['id'=>200000,'joined'=>205000,'left'=>0,'next'=>0,'prev'=>0]
        ];
    }
}

