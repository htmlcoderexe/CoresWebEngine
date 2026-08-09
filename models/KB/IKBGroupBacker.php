<?php
interface IKBGroupBacker
{
    /**
     * Fetches a group's items
     * @param type $id
     */
    public function GetItems($id);
    /**
     * Saves a group's items
     * @param type $id
     * @param item[] $items
     */
    public function SetItems($id, $items);
    /**
     * Locates a group by item ID
     * @param type $id
     */
    public function Find($id) : int;
}