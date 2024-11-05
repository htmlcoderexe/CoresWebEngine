<?php

class Study
{
	public $id;
	public $name;
	public $description;
	private $dataseries;
	
	public function __construct($id)
	{
		$this->id=(int)$id;
		$r=DBHelper::GetOneRow("
		SELECT id,title,description,groupid	
		FROM datacore_studies
		WHERE id={$this->id}
		");
		$this->name=$r['title'];
		$this->description=$r['description'];
		$this->GetDataSeries();
	}
	
	public function GetDataSeries()
	{
		if($this->dataseries!=null)
		{
			return $this->dataseries;
		}
		$this->dataseries=Array();
		$serieslist=DBHelper::GetList("SELECT id FROM datacore_series WHERE study_id={$this->id}");
		$c=count($serieslist);
		for($i=0;$i<$c;$i++)
		{
			$this->dataseries[]=new DataSeries($serieslist[$i]);
		}
	}
	
}


