<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Common;
use Cores\EngineCore;

/**
 * Description of CSRF
 *
 * @author admin
 */
class CSRF
{
    static string $token = "";
    public const FIELD_NAME = 'CSRF';
    public const SECRET_LENGTH = 64;
    public static function SetupSecret()
    {
        $session_secret = random_bytes(self::SECRET_LENGTH);
        $_SESSION['secret_id'] = $session_secret;
    }
    
    public static function SetupToken()
    {
        $token = hash_hmac('sha256', $_SESSION['secret_id'].session_id(), \CSRF_SECRET);
        self::$token = $token;
    }
    public static function VerifyToken()
    {
        $token = EngineCore::POST(self::FIELD_NAME);
        return hash_equals($token, self::$token);
    }
}
