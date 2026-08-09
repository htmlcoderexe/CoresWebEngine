<?php

/**
 * Description of KBPageRevision
 *
 */
class KBPageRevision
{
    public int $id;
    public int $page_id;
    public string $title;
    public string $json;
    public string $html;
    public string $text;
    public int $timestamp;
    public int $userId;
    function __construct(int $id, string $title, string $json, string $text, string $html, int $pageId, int $timestamp, int $userId)
    {
        $this->id = $id;
        $this->title = $title;
        $this->json = $json;
        $this->html = $html;
        $this->text = $text;
        $this->pageId = $pageId;
        $this->timestamp = $timestamp;
        $this->userId = $userId;
    }
}
