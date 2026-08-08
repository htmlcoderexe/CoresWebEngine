<?php

namespace Controllers;

class HomePage
{
    #[\Route('main/default')]
    public static function Homepage()
    {
        echo "home page lol";
        die;
    }    
}
