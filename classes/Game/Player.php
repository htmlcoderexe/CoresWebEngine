<?php

class GameMath
{
	public static function Normalize($number)
	{
		if($number==0)
			return 0;
		return (float)$number/(float)abs((float)$number);
	}
}

class Player
{
	public $X;
	public $Y;
	public $Health;
	public $Level;
	public $XP;
	public function __construct()
	{
		$this->X=0;
		$this->Y=0;
		$this->Health=100;
		$this->Level=1;
		$this->XP=0;
	}

}

class Enemy
{
	public $X;
	public $Y;
	public $Health;
	public $Level;
	public $XP;
	private $lastd;
	public function __construct()
	{
		$this->X=0;
		$this->Y=0;
		$this->Health=100;
		$this->Level=1;
		$this->XP=0;
	}
	public function Move($x,$y)
	{
		$this->X=$x;
		$this->Y=$y;
	}
	public function MoveTowards($x,$y)
	{
		$dx=$this->X-$x;
		$dy=$this->Y-$y;
		$this->lastd[]=Array($dx,$dy);
		if(abs($dx)<abs($dy))
		{
			$this->Y-=GameMath::Normalize($dy);
		}
		else
		{
			$this->X-=GameMath::Normalize($dx);
		}
	}
	public function Tick($player)
	{
		/*
		$this->Y=3;
		$this->X=3;
		//*/
		$this->MoveTowards($player->X,$player->Y);
	} 
}