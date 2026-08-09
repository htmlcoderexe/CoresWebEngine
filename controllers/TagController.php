<?php

namespace Controllers;

use Models\Tags\Tag;
use \Route;
use Cores\EngineCore;

/**
 * Description of TagController
 *
 * @author admin
 */
class TagController
{
    #[Route('tag/suggest')]
    public static function SuggestTag($prefixortype='', $prefix ='')
    {
        $evatype = $prefix != '' ? $prefixortype : '';
        $prefix = $prefix == '' ? $prefixortype : $prefix;
        $suggestions = Tag::GetSuggestions($prefix, $evatype);
        EngineCore::EmitJSON($suggestions);
    }
    #[Route('tag/find')]
    public static function FindByTag($tagortype='', $tag ='')
    {
        $evatype = $tag != '' ? $tagortype : '';
        $tag = $tag == '' ? $tagortype : $tag;
        $results = Tag::Find($evatype, $tag);
        EngineCore::EmitJSON($results);
    }
}
