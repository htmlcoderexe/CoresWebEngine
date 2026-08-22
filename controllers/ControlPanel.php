<?php



namespace Controllers;

use Route;

/**
 * Description of ControlPanel
 *
 */
class ControlPanel
{
    #[Route('cpanel/default','cpanel')]
    public static function ShowCpanel()
    {
        return ['entity_type'=>'cpanel/mainscreen'];
    }
}
