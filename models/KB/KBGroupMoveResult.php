<?php


/**
 * Description of KBGroupMoveResult
 *
 */
class KBGroupMoveResult
{
    public bool $noChange = false;
    public int $leftGroup = 0;
    public int $joinedGroup = 0;
    public int $itemId = 0;
    public int $previousItem = 0;
    public int $nextItem = 0;
    public $affectedItems = [];
    
    public function __construct($noChange = false, $leftGroup = 0, $joinedGroup = 0, $itemId = 0, $previousItem = 0, $nextItem = 0, $affectedItems = [])
    {
        $this->noChange=$noChange;
        $this->leftGroup=$leftGroup;
        $this->joinedGroup=$joinedGroup;
        $this->itemId=$itemId;
        $this->previousItem=$previousItem;
        $this->nextItem=$nextItem;
        $this->affectedItems=$affectedItems;
    }
}
