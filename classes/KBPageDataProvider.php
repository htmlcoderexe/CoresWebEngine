<?php

/**
 * Description of KBPageDataProvider
 *
 */
interface KBPageDataProvider
{
    /**
     * Retrieves all necessary data for a KBPage by PageID.
     * @param type $id Page ID
     */
    public function LoadPage(int $id) : KBPageInfo;
    public function SavePage(KBPageInfo $page);
    public function LoadRevision(int $revisionId) : KBPageRevision;
    public function SaveRevision(KBPageInfo $page) : KBPageRevision;
    public function GetLatestRevisionID(int $pageId) : int;
}
