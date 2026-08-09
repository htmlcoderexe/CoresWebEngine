<?php
namespace Models\KB;

/**
 * Description of KBPageDataProvider
 *
 */
interface IPageDataProvider
{
    /**
     * Retrieves all necessary data for a KBPage by PageID.
     * @param type $id Page ID
     */
    public function LoadPage(int $id) : PageInfo | null;
    public function SavePage(PageInfo $page);
    public function LoadRevision(int $revisionId) : PageRevision | null;
    public function SaveRevision(PageInfo $page) : PageRevision;
    public function GetLatestRevisionID(int $pageId) : int;
}
