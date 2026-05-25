<?php


function innerHTML($element) 
{ 
    $innerHTML = ""; 
    $children  = $element->childNodes;
    if(!$children || count($children)<1)
    {
        return "";
    }
    foreach ($children as $child) 
    { 
        $innerHTML .= $element->ownerDocument->saveHTML($child);
    }

    return $innerHTML; 
} 
function innerHTMLButWithLessHTML($element) 
{ 
    $innerHTML = ""; 
    $children  = $element->childNodes;

    if(!$children || count($children)<1)
    {
        return "";
    }
    foreach ($children as $child) 
    { 
        $innerHTML .= innerHTML($child);
    }

    return $innerHTML; 
} 

function getP($node)
{
    $text ="";
    $p=[
        'type'=>'paragraph',
        'data'=>[
        ]
    ];
    $prop = 'text';
    if(!$node->childNodes || count($node->childNodes)<1)
    {
        return null;
    }
    foreach($node->childNodes as $cn)
    {
        if($cn->nodeName=='img')
        {
            $p['data']['url'] = $cn->attributes['src']->value;
            $p['type'] = 'image';
            $prop = 'caption';
        }
        else
        {
            $text.=$node->ownerDocument->saveHTML($cn);
        }
    }
    $p['data'][$prop]=$text;
    return $p;
}

function getBQ($node)
{
    $text = innerHTML($node);
    $p=[
        'type'=>'quote',
        'data'=>[
            'text'=>$text
        ]
    ];
    return $p;
}
function getCode($node)
{
    
    $text = innerHTMLButWithLessHTML($node);
    $p=[
        'type'=>'code',
        'data'=>[
            'code'=>$text
        ]
    ];
    return $p;
}

function getTable($node)
{
    $p=[
        'type'=>'table',
        'data'=>[
            'content'=>[]
        ]
    ];
    $withHeaders=false;
    if(!$node->childNodes || count($node->childNodes)<1)
    {
        return null;
    }
    foreach($node->childNodes as $child)
    {
        if($child->nodeName!="tr")
        {
            continue;
        }
        if(!$child->childNodes || count($child->childNodes)<1)
        {
            continue;
        }
        $row=[];
        foreach($child->childNodes as $cell)
        {
            if($cell->nodeName == 'th')
            {
                $text = innerHTML($cell);
                $row[]=$text;
                $withHeaders=true;
            }
            if($cell->nodeName == 'td')
            {
                $text = innerHTML($cell);
                $row[]=$text;
            }
            
        }
        if(count($row)>0)
        {
            $p['data']['content'][]=$row;
        }
    }
    $p['data']['withHeaders']=$withHeaders;
    return $p;
}

function getList($node)
{
    $type = $node->nodeName == 'ul' ? 'unordered' : 'ordered';
    $p=[
        'type'=>'list',
        'data'=>[
            'style'=>$type,
            'items'=>[]
        ]
    ];
    if(!$node->childNodes || count($node->childNodes)<1)
    {
        return null;
    }
    foreach($node->childNodes as $child)
    {
        if($child->nodeName!="li")
        {
            continue;
        }
        $text = innerHTML($child);
        $li = [
            'content'=>$text,
            'items'=>[],
            'meta'=>[]
        ];
        $p['data']['items'][]=$li;
    }
    return $p;
}

function getH($node)
{
    $text = innerHTML($node);
    $level = intval(($node->nodeName)[1]);
    $p=[
        'type'=>'header',
        'data'=>[
            'text'=>$text,
            'level'=>$level
        ]
    ];
    return $p;
}

function get_ejs_from_crappy_html_maybe($crappy_html)
{
    if(false && strpos($crappy_html,'data-num="')===false)
    {
        return "nope";
    }
    $doc = new DOMDocument();
    $doc->loadHTML("<html><body><div id='container'>$crappy_html</div></body></html>", LIBXML_NOERROR);
    $container = $doc->getElementById('container');
    $ejs = [];
    foreach($container->childNodes as $node)
    {
        $block = null;
        switch($node->nodeName)
        {
            case 'p':
            case 'div':
            {
                $block = getP($node);
                break;
            }
            case 'blockquote':
            {
                $block = getBQ($node);
                break;
            }
            case 'pre':
            {
                $block = getCode($node);
                break;
            }
            case 'table':
            {
                $block = getTable($node);
                break;
            }
            case 'ol':
            case 'ul':
            {
                $block = getList($node);
                break;
            }
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
            {
                $block = getH($node);
                break;
            }
        }
        if($block)
        {
            $ejs[]=$block;
        }
    }
    return $ejs;
}

function ModuleAction_kb_migrate_test($params)
{
    $id = intval($params[0]);
    $p=KBPage::Load($id);
    if(!$p)
    {
        echo "no";
        die;
    }
    $crappy_html = $p->html;
    $ejs = get_ejs_from_crappy_html_maybe($crappy_html);
    var_dump($ejs);
    die;
}


function table_copy($source, $destination)
{
        $qback="DROP TABLE IF EXISTS $destination; "
                . "CREATE TABLE $destination LIKE $source; " 
                ."INSERT INTO $destination SELECT * FROM $source; ";
        DBHelper::RunVoid($qback,[]);
    
}

function ModuleAction_kb_migrate_newrev($params)
{
    
    $mode = $params[0] ?? 'forward';
    if($mode == 'rollback')
    {
        table_copy("kb_pages_pre_migration","kb_pages");
        table_copy("kb_page_revisions_pre_migration","kb_page_revisions");
        echo "rollback done! ";
        echo '<a href="/kb/migrate/newrev/forward">try again</a>';
        die();
    }
    table_copy("kb_pages","kb_pages_pre_migration");
    table_copy("kb_page_revisions","kb_page_revisions_pre_migration");
    DBHelper::RunVoid("DROP TABLE IF EXISTS kb_page_revisions_legacy",[]);
    
    $q_rename_revs = "ALTER TABLE kb_page_revisions RENAME kb_page_revisions_legacy";
    $q_make_new_revs = "CREATE TABLE IF NOT EXISTS `kb_page_revisions` ("
        ."  `id` int NOT NULL AUTO_INCREMENT,"
        ."  `page_id` int NOT NULL,"
        ."  `title` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, "
        ."  `content_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,"
        ."  `content_plaintext` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,"
        ."  `content_html` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,"
        ."  `timestamp` int NOT NULL,"
        ."  `userid` int NOT NULL,"
        ."  PRIMARY KEY (`id`)"
        .") ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $q_add_search_index_revs = "ALTER TABLE `kb_page_revisions` ADD FULLTEXT KEY `search` (`content_plaintext`);";
    $q_mod_revisions = "ALTER TABLE `kb_pages` "
            . "ADD `latest` INT NOT NULL AFTER `creator_id`, "
            . "ADD `ejsdoc` TEXT NOT NULL AFTER `latest`, "
            . "ADD `html` TEXT NOT NULL AFTER `ejsdoc`, "
            . "ADD `text` TEXT NOT NULL AFTER `html`";
    $q_add_search_index_pages = "ALTER TABLE `kb_pages` ADD FULLTEXT KEY `search` (`text`);";
    DBHelper::BeginTransaction();
    DBHelper::RunVoid($q_rename_revs,[]);
    DBHelper::RunVoid($q_make_new_revs, []);
    echo "make new";
    DBHelper::RunVoid($q_add_search_index_revs, []);
    echo "added idx1";
    DBHelper::RunVoid($q_mod_revisions, []);
    echo "modded pages";
    DBHelper::RunVoid($q_add_search_index_pages, []);
    echo "added idx2";
    $old_revs_fields = ['page_id','content_json','content_raw','content_html','timestamp','userid'];
    // go thru all old revisions
    $q_sel_all_revs = DBHelper::Select("kb_page_revisions_legacy", $old_revs_fields,[],['page_id'=>'ASC','timestamp'=>'DESC']);
    $all_revs = DBHelper::RunTable($q_sel_all_revs,[]);
    $pagedata_fields = ['id','title'];
    $q_get_page_titles = DBHelper::Select("kb_pages",$pagedata_fields,[]);
    $result_page_titles = DBHelper::RunTable($q_get_page_titles,[]);
    $page_titles = [];
    foreach($result_page_titles as $page_title_entry)
    {
        $page_titles[$page_title_entry['id']] = $page_title_entry['title'];
    }
    $c=count($all_revs);
    $timestamp = 0;
    $currentpage = 0;
    $page_updates = [];
    $json_migrate_list = [];
    // write revisions over to new table
    // and prepare a list of pages to update
    for($i=0;$i<$c;$i++)
    {
        $rev = $all_revs[$i];
        // insert into the new revisions table
        $insert = [null, $rev['page_id'], $page_titles[$rev['page_id']],$rev['content_json'],$rev['content_raw'],$rev['content_html'],$rev['timestamp'],$rev['userid']];
        DBHelper::Insert('kb_page_revisions',$insert);
        // update current page with new revision ID
        // the sorting here ensures that every first entry
        // of a new page ID is the most recent
        if($rev['page_id']!=$currentpage)
        {
            $newest_rev = DBHelper::GetLastId();
            $rev['latest']=$newest_rev;
            $page_updates[$rev['page_id']] = $rev;
            // if this is empty, add to migration list
            if(!$rev['content_json'])
            {
                $json_migrate_list[$rev['page_id']] = $rev;
            }
            $currentpage = $rev['page_id'];
        }
    }
    // prepare json processing where needed
    foreach($json_migrate_list as $m)
    {
        $ejs = get_ejs_from_crappy_html_maybe($m['content_html']);
        $ejssstr = json_encode(['blocks'=>$ejs]);
        $page_updates[$m['page_id']]['content_json'] = $ejssstr;
        $doc = EditorJSDocument::FromBlocks($ejs);
        $page_updates[$m['page_id']]['content_html'] = $doc->GetHTML();
        $page_updates[$m['page_id']]['content_raw'] = $doc->GetPlainText();
        $page_updates[$m['page_id']]['update_rev'] = true;
    }
    // do actual updates now
    foreach($page_updates as $update)
    {
        // if migrated, update the latest revision's data
        // not gonna bother with every revision fuck it
        if(isset($update['update_rev']))
        {
            DBHelper::Update('kb_page_revisions',
                    ['content_json'=>$update['content_json'],
                        'content_plaintext'=>$update['content_raw']],
                    ['id'=>$update['latest']]);
        }
        // refresh page
        DBHelper::Update('kb_pages',
                    ['ejsdoc'=>$update['content_json'],
                        'text'=>$update['content_raw'],
                        'html'=>$update['content_html'],
                        'latest'=>$update['latest']],
                ['id'=>$update['page_id']]);
    }
    $fields_all_groups = ['collectionId','entityId','prev','next'];
    $q_all_groups = DBHelper::Select('kbgroups',$fields_all_groups,[],['collectionId'=>'ASC','ordinal'=>'ASC']);
    $all_groups = DBHelper::RunTable($q_all_groups,[]);
    $current = 0;
    var_dump($all_groups);
    // that should be it - update navlinnks
    foreach($all_groups as $entry)
    {
        if($entry['collectionId']!=$current)
        {
            $p = KBPage::Load($entry['collectionId']);
            $p->RenderHTML();
            $p->SaveNewRevision();
            $current = $entry['collectionId'];
        }
        $p = KBPage::Load($entry['entityId']);
        if($p)
        {
            echo "<br >doing ".$p->id;
            //var_dump($entry);
            $p->UpdateChapterNav($entry['collectionId'],$entry['prev'],$entry['next']);
            $p->SaveNewRevision();
//var_dump($p->ejsdoc);
        }
    }
    DBHelper::Commit();
        echo "migration done! ";
        echo '<a href="/kb/migrate/newrev/rollback">rollback</a>';
        die();
}

