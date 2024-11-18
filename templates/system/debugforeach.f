<?php
function tpl_debugforeach_count($params)
{
    $count=$params;
    $output=[];
    for($i=0;$i<$count;$i++)
    {
        $output[]=$i+1;
    }
    return $output;
}