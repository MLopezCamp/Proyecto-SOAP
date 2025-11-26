<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

class EnvLoader
{
    private static $loaded = false;

    public static function load()
    {
        if (self::$loaded) return;

        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();

        self::$loaded = true;
    }
}
