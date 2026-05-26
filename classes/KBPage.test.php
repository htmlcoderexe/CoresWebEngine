<?php
require_once "MiniTest.php";
class KBPageDataProviderMock implements IKBPageDataProvider
{
    
    
    public function GetLatestRevisionID(int $pageId): int
    {
        
    }

    public function LoadPage(int $id): \KBPageInfo
    {
        
    }

    public function LoadRevision(int $revisionId): \KBPageRevision
    {
        
    }

    public function SavePage(\KBPageInfo $page)
    {
        
    }

    public function SaveRevision(\KBPageInfo $page): \KBPageRevision
    {
        
    }
}



class KBPageTest extends MiniTest
{
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
    
    public const testEJS1 = <<<EJS1
        {"blocks":[{
            "type": "chapternav",
            "data": {
                "modified": false,
                "prev": 0,
                "index": 1400,
                "next": 0
            }
        },
        {
            "type": "paragraph",
            "data": {
                "text": "This is a test."
            }
        }]}
EJS1;    
    public IKBGroupBacker $groupDb;
    public IKBPageDataProvider $pageDb;
    
    public function __construct()
    {
        $this->TestChapterNav();
    }
    
    public function Reset()
    {
        $this->groupDb = new KBGroupTestBacker(self::testdata1);
        $this->pageDb = new KBPageDataProviderMock();
    }
    
    public function TestChapterNav()
    {
        $this->tests[]=[
            'title'=>"Process a chapterNav, simple case, adding page 3000 to group 1400 at the end",
            'group'=>"ChapterNav",
            'body'=>function(){
                $this->Reset();
                $newpg = new KBPage(PageProvider: $this->pageDb,
                        GroupProvider: $this->groupDb,
                        id: 3000,
                        title: "test page1",
                        text: "",
                        html: "",
                        ejsdoc: EditorJSDocument::FromJSON(self::testEJS1));
                
                $chapternav = $newpg->ejsdoc->GetChapterNav();
                $g14_before = KBGroup::Load(backer: $this->groupDb, id: 1400);
                $newnav = $newpg->ActionChapterNav($chapternav);
                $g14_after = KBGroup::Load(backer: $this->groupDb, id: 1400);
                return [$g14_before->items,$chapternav, $newnav, $g14_after->items];
            },
            'expect'=>[
                [
                    ['id'=>600,'prev'=>0,'next'=>700],
                    ['id'=>700,'prev'=>600,'next'=>2900],
                    ['id'=>2900,'prev'=>700,'next'=>0]
                ],
                [
                'type'=>'chapternav',
                'data'=>[
                    'modified'=> false,
                    'prev'=>0,
                    'index'=>1400,
                    'next'=>0
                    ]
                ],
                [
                'type'=>'chapternav',
                'data'=>[
                    'modified'=> false,
                    'prev'=>2900,
                    'index'=>1400,
                    'next'=>0
                    ]
                ],
                [
                    ['id'=>600,'prev'=>0,'next'=>700],
                    ['id'=>700,'prev'=>600,'next'=>2900],
                    ['id'=>2900,'prev'=>700,'next'=>3000],
                    ['id'=>3000,'prev'=>2900,'next'=>0],
                ]
            ]
        ]; 
    }
    
}