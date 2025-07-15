<?php

class DataSeries
{
	const TYPE_INTERVAL=0;
	const TYPE_OPTION=1;
	const TYPE_VALUE=2;
	public $id;
	public $studyid;
	public $description;
	private $datapoints;
	
	public function __construct($id)
	{
		$this->id=(int)$id;
                $q=DBHelper::Select('datacore_series',['id','study_id','description'],['id'=>$this->id]);
		$r=DBHelper::RunRow($q,[$this->id]);
		$this->studyid=$r['study_id'];
		$this->description=$r['description'];
		$this->GetDataPoints();
	}
	
	public function GetDataPoints()
	{
		if(isset($this->datapoints))
		{
			return $this->datapoints;
		}
		$this->datapoints=Array();
                $q = DBHelper::Select("datacore_datapoints",['id','value','timestamp'],['series_id'=>$this->id],['timestamp'=>'ASC']);
                $points = DBHelper::RunList($q,[$this->id]);
		$c=count($points);
		if($c>0)
		{
			for($i=0;$i<$c;$i++)
			{
				$this->datapoints[]=new DataPoint($points[$i],$this);
			}
		}
		return $this->datapoints;
	}
	
	public function GetValues()
	{
		$points=$this->GetDataPoints();
		$c=count($points);
		$values=Array();
		for($i=0;$i<$c;$i++)
		{
			$values[]=$points[$i]->value;
		}
		return $values;
	}
	
}
