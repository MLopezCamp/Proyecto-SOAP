<?php
require_once __DIR__ . "/../vendor/autoload.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Cargar .env UNA sola vez
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

class JwtHelper
{
    public static function generarToken($user)
    {
        $payload = [
            'sub'  => $user['user_id'],
            'role' => $user['rol_id'],
            'iat'  => time(),
            'exp'  => time() + 120 // 2 minutos
        ];

        return JWT::encode($payload, $_ENV['JWT_KEY'], 'HS256');
    }

    public static function validarToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_KEY'], 'HS256'));
            return [
                'valid' => true,
                'data'  => $decoded
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
