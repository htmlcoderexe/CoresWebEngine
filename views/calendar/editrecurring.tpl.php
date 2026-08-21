<?php

function TemplateFunction_editrecurring_duration($minutes)
{
    return sprintf("%02d:%02d",floor($minutes/60),$minutes % 60);
}

