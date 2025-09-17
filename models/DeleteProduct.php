<?php

require_once "../vendor/econea/nusoap/src/nusoap.php";
require_once "../config/Database.php";

$namespace = "DeleteProductSOAP";
$server = new soap_server();
$server->configureWSDL('SoapService', $namespace);

// Registrar método
$server->register(
    'DeleteProductService',
    array('producto_id' => 'xsd:int'),
    array('return' => 'xsd:string'),
    $namespace,
    false,
    'rpc',
    'encoded',
    'Eliminar un producto por ID'
);

// Implementación del servicio
function DeleteProductService($producto_id) {
    global $pdo;
    if (!$pdo) return "conexión a BD no disponible.";

    if (!$producto_id || $producto_id <= 0) {
        return "ID de producto inválido.";
    }

    try {
        // Verificar que el producto exista y no esté eliminado
        $check = $pdo->prepare("SELECT * FROM tb_producto WHERE producto_id = :id AND deleted = 0");
        $check->bindParam(':id', $producto_id, PDO::PARAM_INT);
        $check->execute();
        $producto = $check->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            return "el producto no existe o ya fue eliminado.";
        }

        // Soft delete
        $stmt = $pdo->prepare("UPDATE tb_producto SET deleted = 1 WHERE producto_id = :id");
        $stmt->bindParam(':id', $producto_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0 ? "Producto eliminado correctamente" : "No se pudo eliminar el producto.";
    } catch (PDOException $e) {
        return "Error en BD: " . $e->getMessage();
    }
}

// Procesar petición SOAP
$POST_DATA = file_get_contents("php://input");
$server->service($POST_DATA);
exit();
?>
