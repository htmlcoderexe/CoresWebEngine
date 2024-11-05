<?php
class InventoryTransaction
{
	public $id;
	public $item;
	public $itemamount;
	public $cost;
	public $timestamp;
	public $type;
	public $comment;
	private $eva;
	
	public static $objectType='inventory_transaction';
	
	public function __construct($id)
	{
		$this->eva=new EVA($id);
		
		//echo var_dump($this);
		$this->item=		$this->eva->GetSingleAttribute('itemid');
		
		$this->itemamount=	$this->eva->GetSingleAttribute('item_amount');
		$this->cost=		$this->eva->GetSingleAttribute('cost');
		$this->timestamp=	$this->eva->GetSingleAttribute('timestamp');
		$this->type=		$this->eva->GetSingleAttribute('transaction_type');
		$this->comment=		$this->eva->GetSingleAttribute('itemid');
		
	}
	
	public function Save()
	{
		$this->eva->SetSingleAttribute('itemid',$this->item);
	    $this->eva->SetSingleAttribute('item_amount',$this->itemamount);
	    $this->eva->SetSingleAttribute('cost',$this->cost);
	    $this->eva->SetSingleAttribute('timestamp',$this->timestamp);
	    $this->eva->SetSingleAttribute('transaction_type',$this->type);
	    $this->eva->SetSingleAttribute('itemid',$this->comment);
		$this->eva->Save();
	}
	
	public static function FindByType($type)
	{
		return EVA::GetByProperty('transaction_type',$type,InventoryTransaction::$objectType);
	}
}


