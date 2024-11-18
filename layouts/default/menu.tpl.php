<?php

function TemplateFunction_menu_menulinks()
{
    return DBHelper::RunTable("SELECT link,text FROM menulinks",[]);
}