<?php

use Common\DBHelper as DBHelper;
function TemplateFunction_menu_menulinks()
{
    return DBHelper::RunTable("SELECT link,text FROM menulinks",[]);
}