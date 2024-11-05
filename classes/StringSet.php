<?php
class StringSet
{
	public $strings;
	public function __construct($name,$language,$fallback)
	{
		$file=file_get_contents("modules\\kb\\strings.txt");
		$file=explode("\n",$file);
		$c=count($file);
		$this->strings=Array();
		for($i=0;$i<$c;$i++)
		{
			list($key,$value)=explode("=",$file[$i],2);
			$this->strings[$key]=$value;
		}
		var_dump($this->strings);
	}

}
