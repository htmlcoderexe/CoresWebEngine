<?php

function TemplateFunction_showtags_tagtype($param)
{
    $bits = explode(":",$param);
    if(count($bits)>1)
    {
        return $bits[0];
    }
    return "";
}

function TemplateFunction_showtags_tagbare($param)
{
    $bits = explode(":",$param);
    if(count($bits)>1)
    {
        return $bits[1];
    }
    return $param;
}