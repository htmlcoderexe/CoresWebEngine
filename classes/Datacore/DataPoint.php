<?php

class DataPoint
{
	public $id;
	public $series;
	public $value;
	public $timestamp;
	
	public function __construct($row,$parent)
	{
		$this->id=$row['id'];
		$this->series=$parent;
		$this->value=$row['value'];
		$this->timestamp=$row['timestamp'];
	}
	
}
