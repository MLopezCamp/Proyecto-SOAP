<?php
// Configuración de la conexión a la base de datos
$dsn = 'mysql:host=127.0.0.1;port=3306;dbname=proyecto';
$username = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Error de conexión a BD: " . $e->getMessage(), 3, __DIR__ . '/php_errors.log');
    $pdo = null;
}