<?php
require_once "../vendor/autoload.php";
require_once "../vendor/econea/nusoap/src/nusoap.php";
require_once "../config/Database.php";
require_once "../models/middleware/TokenValidator.php";

$namespace = "SelectUserSOAP";
$server = new soap_server();
$server->configureWSDL('SoapService', $namespace);

$server->register(
    'SelectUserService',
    array('user_id' => 'xsd:int'),
    array('return' => 'xsd:string'),
    $namespace,
    false,
    'rpc',
    'encoded',
    'Obtener un usuario por ID'
);

function SelectUserService($user_id) {

    // 1. Leer token
    $token = TokenValidator::extractToken();
    $valid = TokenValidator::validate($token);

    if (!$valid['ok']) {
        return "<error>{$valid['mensaje']}</error>";
    }

    // 2. Conexión BD
    $db = new Database();
    $pdo = $db->getConnection();
    if (!$pdo) return "Conexión a BD no disponible";

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ? json_encode($user) : "Usuario no encontrado";

    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}

$POST_DATA = file_get_contents("php://input");
$server->service($POST_DATA);
exit();
