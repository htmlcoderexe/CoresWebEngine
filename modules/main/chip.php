<?php
function ModuleAction_main_chip_getcommands()
{
    $user = EngineCore::$CurrentUser;
    $commands = Chip::GetCommands($user->userid);
    EngineCore::RawModeOn();
    HTTPHeaders::ContentType("application/json");
    echo json_encode($commands);
    die();
}