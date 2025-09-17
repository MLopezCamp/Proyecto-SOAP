<?php
// Configuración del servidor SOAP
require_once "../vendor/econea/nusoap/src/nusoap.php";
require_once "../config/Database.php";

$namespace = "DeleteUserSOAP";
$server = new soap_server();
$server->configureWSDL('SoapService', $namespace);

// Registrar método
$server->register(
    'DeleteUserService',
    array('user_id' => 'xsd:int'),
    array('return' => 'xsd:string'),
    $namespace,
    false,
    'rpc',
    'encoded',
    'Eliminar un usuario por ID'
);

// Función de eliminación
function DeleteUserService($user_id) {
    global $pdo;
    if (!$pdo) return "Conexión a BD no disponible";

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0 ? "Usuario eliminado correctamente" : "Usuario no encontrado";
    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}

// Procesamiento SOAP
$POST_DATA = file_get_contents("php://input");
$server->service($POST_DATA);
exit();
?>
