<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/EnvLoader.php';
require_once __DIR__ . '/../../config/Database.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TokenValidator
{
    /**
     * Extrae el token del header HTTP Authorization
     */
    public static function extractToken()
    {
        $headers = apache_request_headers();
        
        if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
            return null;
        }
        
        $authHeader = $headers['Authorization'] ?? $headers['authorization'];
        
        // Limpiar prefijo "Bearer " si existe
        if (strpos($authHeader, 'Bearer ') === 0) {
            return trim(substr($authHeader, 7));
        }
        
        return trim($authHeader);
    }
    
    public static function validate($token)
    {
        // Limpiar prefijo "Bearer " si existe
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }
        
        // Limpiar espacios
        $token = trim($token);
        
        return self::validarToken($token);
    }

    public static function validarToken($token)
    {
        // Cargar .env globalmente
        EnvLoader::load();

        // Validar existencia de JWT_KEY
        if (!isset($_ENV['JWT_KEY']) || empty($_ENV['JWT_KEY'])) {
            return [
                "estado" => "ERROR",
                "mensaje" => "JWT_KEY no está definida en el .env",
                "permitido" => false
            ];
        }

        // Validar token enviado
        if (!$token || trim($token) === "") {
            return [
                "estado" => "ERROR",
                "mensaje" => "Token no enviado.",
                "permitido" => false
            ];
        }

        try {
            // Decodificar JWT
            $decoded = JWT::decode($token, new Key($_ENV['JWT_KEY'], 'HS256'));

            // Verificar expiración
            if ($decoded->exp < time()) {
                return [
                    "estado" => "ERROR",
                    "mensaje" => "El token ha expirado.",
                    "permitido" => false
                ];
            }

            // Token válido - DEVOLVER ROL_ID
            return [
                "estado" => "OK",
                "mensaje" => "Token válido.",
                "permitido" => true,
                "usuario" => $decoded,
                "user_id" => $decoded->sub,
                "rol_id" => $decoded->role  // ROL DEL USUARIO
            ];

        } catch (Exception $e) {
            return [
                "estado" => "ERROR",
                "mensaje" => "Token inválido: " . $e->getMessage(),
                "permitido" => false
            ];
        }
    }
}