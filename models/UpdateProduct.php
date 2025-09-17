<?php
// Servicio SOAP para actualizar productos
require_once "../vendor/econea/nusoap/src/nusoap.php";
require_once "../config/Database.php";

$namespace = "UpdateProductSOAP";
$server = new soap_server();
$server->configureWSDL('SoapService', $namespace);

// Definir estructura de entrada
$server->wsdl->addComplexType(
    'UpdateProduct',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'producto_id' => array('name' => 'producto_id', 'type' => 'xsd:int'),
        'nombre'      => array('name' => 'nombre', 'type' => 'xsd:string'),
        'marca'       => array('name' => 'marca', 'type' => 'xsd:string'),
        'categoria_id'=> array('name' => 'categoria_id', 'type' => 'xsd:int'),
        'precio'      => array('name' => 'precio', 'type' => 'xsd:decimal'),
        'cantidad'    => array('name' => 'cantidad', 'type' => 'xsd:int'),
        'descripcion' => array('name' => 'descripcion', 'type' => 'xsd:string')
    )
);

// Registrar método
$server->register(
    'UpdateProductService',
    array('data' => 'tns:UpdateProduct'),
    array('return' => 'xsd:string'),
    $namespace,
    false,
    'rpc',
    'encoded',
    'Actualizar un producto'
);

// Implementación del servicio
function UpdateProductService($data) {
    global $pdo;
    if (!$pdo) return "conexión a BD no disponible.";

    // Validaciones
    if (!isset($data['producto_id']) || $data['producto_id'] <= 0) {
        return "ID de producto inválido.";
    }
    if (!isset($data['nombre']) || strlen(trim($data['nombre'])) < 3) {
        return "el nombre del producto debe tener al menos 3 caracteres.";
    }
    if (!isset($data['marca']) || trim($data['marca']) === "") {
        return "la marca es obligatoria.";
    }
    if (!isset($data['precio']) || $data['precio'] <= 0) {
        return "el precio debe ser mayor a 0.";
    }
    if (!isset($data['cantidad']) || $data['cantidad'] < 0) {
        return "la cantidad no puede ser negativa.";
    }
    if (!isset($data['categoria_id']) || $data['categoria_id'] <= 0) {
        return "categoría inválida.";
    }

    try {
        // Verificar que el producto exista
        $checkProd = $pdo->prepare("SELECT * FROM tb_producto WHERE producto_id = :id AND deleted = 0");
        $checkProd->bindParam(':id', $data['producto_id'], PDO::PARAM_INT);
        $checkProd->execute();
        $producto = $checkProd->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            return "el producto no existe o está eliminado.";
        }

        // Verificar que la categoría exista y esté activa
        $checkCat = $pdo->prepare("SELECT * FROM tb_categoria WHERE categoria_id = :id AND estado = 'Activo'");
        $checkCat->bindParam(':id', $data['categoria_id'], PDO::PARAM_INT);
        $checkCat->execute();
        $categoria = $checkCat->fetch(PDO::FETCH_ASSOC);

        if (!$categoria) {
            return "la categoría no existe o está inactiva.";
        }

        // Actualizar producto
        $stmt = $pdo->prepare("UPDATE tb_producto 
                               SET nombre = :nombre, 
                                   marca = :marca, 
                                   categoria_id = :categoria_id,
                                   precio = :precio, 
                                   cantidad = :cantidad, 
                                   descripcion = :descripcion
                               WHERE producto_id = :producto_id");

        $stmt->bindParam(':producto_id', $data['producto_id']);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':marca', $data['marca']);
        $stmt->bindParam(':categoria_id', $data['categoria_id']);
        $stmt->bindParam(':precio', $data['precio']);
        $stmt->bindParam(':cantidad', $data['cantidad']);
        $stmt->bindParam(':descripcion', $data['descripcion']);

        $stmt->execute();

        return $stmt->rowCount() > 0 ? "Producto actualizado correctamente" : "No hubo cambios en el producto.";
    } catch (PDOException $e) {
        return "Error en BD: " . $e->getMessage();
    }
}

// Procesar petición SOAP
$POST_DATA = file_get_contents("php://input");
$server->service($POST_DATA);
exit();
?>
