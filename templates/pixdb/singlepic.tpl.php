<?php

function TemplateFunction_singlepic_tagtype($param)
{
    $bits = explode(":",$param);
    if(count($bits)>1)
    {
        return $bits[0];
    }
    return "";
}

function TemplateFunction_singlepic_tagbare($param)
{
    $bits = explode(":",$param);
    if(count($bits)>1)
    {
        return $bits[1];
    }
    return $param;
}