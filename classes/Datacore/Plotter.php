<?php

 class Plotter
{
	public $width;
	public $height;
	public $data;
	public $imagehandle;
	public function __construct($data,$w,$h)
	{
		$this->data=$data;
		$this->width=$w;
		$this->height=$h;
		$this->imagehandle=imagecreatetruecolor($this->width,$this->height);
	}
	
	public function OutputImage()
	{
		// deprecated
		die;
	}
	
	public function OutputGraph()
	{
		//imagealphablending($this->imagehandle,true);
		imagefill($this->imagehandle,0,0,0x7fFFFFFF);
		$points=$this->data->GetValues();
		$points=array_map("intval",$points);
		//shuffle($points);
		//sort($points);
		//var_dump(max($points));
		$ratew=($this->width-8)/(count($points)-1);
		$rateh=($this->height-8)/max($points);
		//$rateh=1;
		$c=count($points);
		imageline($this->imagehandle,0,$this->height-8,$this->width,$this->height-8,0xFF0000);
		imageline($this->imagehandle,8,0,8,$this->height,0xFF0000);
		for($i=0;$i<($c-1);$i++)
		{
			$x0=$ratew*$i;
			$x0+=8;
			
			$y0=$rateh*$points[$i];
			$y0=$this->height-($y0+8);
			
			$x1=$ratew*($i+1);
			$x1+=8;
			
			$y1=$rateh*$points[$i+1];
			$y1=$this->height-($y1+8);
			imageline($this->imagehandle,$x1,0,$x1,$this->height,0xC0C0C0);
			imageline($this->imagehandle,$x0,$y0,$x1,$y1,0);
			
		}
		header("Content-Type: image/png");
		imagepng($this->imagehandle);
		die;
	}
}
