<?php
//Servicio SOAP para insertar productos
require_once "../vendor/econea/nusoap/src/nusoap.php";
require_once "../config/Database.php";

$namespace = "InsertProductSOAP";
$server = new soap_server();
$server->configureWSDL('SoapService', $namespace);

// Definir estructura de entrada
$server->wsdl->addComplexType(
    'InsertProduct',
    'complexType',
    'struct',
    'all',
    '',
    array(
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
    'InsertProductService',
    array('data' => 'tns:InsertProduct'),
    array('return' => 'xsd:string'),
    $namespace,
    false,
    'rpc',
    'encoded',
    'Insertar un producto'
);

// Implementación del servicio
function InsertProductService($data) {
    global $pdo;
    if (!$pdo) return "Error: conexión a BD no disponible.";

    // Validaciones
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
        // Verificar que la categoría exista y esté activa
        $check = $pdo->prepare("SELECT * FROM tb_categoria WHERE categoria_id = :id AND estado = 'Activo'");
        $check->bindParam(':id', $data['categoria_id'], PDO::PARAM_INT);
        $check->execute();
        $categoria = $check->fetch(PDO::FETCH_ASSOC);

        if (!$categoria) {
            return "la categoría no existe o está inactiva.";
        }

        // Insertar producto
        $stmt = $pdo->prepare("INSERT INTO tb_producto 
            (nombre, marca, categoria_id, precio, cantidad, descripcion, deleted, created_date)
            VALUES (:nombre, :marca, :categoria_id, :precio, :cantidad, :descripcion, 0, NOW())");

        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':marca', $data['marca']);
        $stmt->bindParam(':categoria_id', $data['categoria_id']);
        $stmt->bindParam(':precio', $data['precio']);
        $stmt->bindParam(':cantidad', $data['cantidad']);
        $stmt->bindParam(':descripcion', $data['descripcion']);

        $stmt->execute();

        return "Producto insertado correctamente";
    } catch (PDOException $e) {
        return "Error en BD: " . $e->getMessage();
    }
}

// Procesar petición SOAP
$POST_DATA = file_get_contents("php://input");
$server->service($POST_DATA);
exit();
?>
