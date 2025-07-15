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
                $q=DBHelper::Select('datacore_studies', ['id','title','description','groupid'], ['id'=>$this->id]);
		$r=DBHelper::RunRow($q,[$this->id]);
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
                $q=DBHelper::Select('datacore_series',['id'],['study_id'=>$this->id]);
		$serieslist=DBHelper::RunList($q,[$this->id]);
		$c=count($serieslist);
		for($i=0;$i<$c;$i++)
		{
			$this->dataseries[]=new DataSeries($serieslist[$i]);
		}
	}
	
}


