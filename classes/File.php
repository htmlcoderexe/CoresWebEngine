<?php
class File
{
    public $id;
    public $fname;
    public $fullname;
    public $filext;
    public $timestamp;
    public $comment;
    public $prev;

    function __construct($id)
    {
        $id = (int) $id;
        $filedata = new EVA($id);
    }

    function Create()
    {

    }
}

