<?php

function TemplateFunction_editevent_duration($minutes)
{
    return sprintf("%02d:%02d",floor($minutes/60),$minutes % 60);
}

function TemplateFunction_editevent_sprintf(...$args)
{
    $fmt = array_shift($args);
    return sprintf($fmt,...$args);
}