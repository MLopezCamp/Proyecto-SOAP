<?php
// Servicio SOAP para listar productos por categoría
require_once "../vendor/econea/nusoap/src/nusoap.php";
require_once "../config/Database.php";

$namespace = "SelectProductsByCategorySOAP";
$server = new soap_server();
$server->configureWSDL('SoapService', $namespace);

// Definir tipo de salida (producto)
$server->wsdl->addComplexType(
    'Product',
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

// Definir array de productos
$server->wsdl->addComplexType(
    'ProductArray',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Product[]')
    ),
    'tns:Product'
);

// Registrar método
$server->register(
    'SelectProductsByCategoryService',
    array('categoria_id' => 'xsd:int'),
    array('return' => 'tns:ProductArray'),
    $namespace,
    false,
    'rpc',
    'encoded',
    'Listar productos de una categoría activa'
);

// Implementación
function SelectProductsByCategoryService($categoria_id) {
    global $pdo;
    if (!$pdo) return array();

    if (!$categoria_id || $categoria_id <= 0) {
        return array(array(
            'producto_id' => 0,
            'nombre'      => "Error: ID de categoría inválido",
            'marca'       => "",
            'categoria_id'=> 0,
            'precio'      => 0,
            'cantidad'    => 0,
            'descripcion' => ""
        ));
    }

    try {
        // Validar si la categoría está activa
        $check = $pdo->prepare("SELECT * FROM tb_categoria WHERE categoria_id = :id");
        $check->bindParam(':id', $categoria_id, PDO::PARAM_INT);
        $check->execute();
        $categoria = $check->fetch(PDO::FETCH_ASSOC);

        if (!$categoria) {
            return array(array(
                'producto_id' => 0,
                'nombre'      => "Error: la categoría no existe",
                'marca'       => "",
                'categoria_id'=> 0,
                'precio'      => 0,
                'cantidad'    => 0,
                'descripcion' => ""
            ));
        }

        if ($categoria['estado'] !== 'Activo') {
            return array(array(
                'producto_id' => 0,
                'nombre'      => "No hay productos asociados a la categoría (inactiva)",
                'marca'       => "",
                'categoria_id'=> $categoria_id,
                'precio'      => 0,
                'cantidad'    => 0,
                'descripcion' => ""
            ));
        }

        // Traer productos activos y no eliminados
        $stmt = $pdo->prepare("SELECT producto_id, nombre, marca, categoria_id, precio, cantidad, descripcion
                               FROM tb_producto
                               WHERE categoria_id = :id AND deleted = 0");
        $stmt->bindParam(':id', $categoria_id, PDO::PARAM_INT);
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$productos) {
            return array(array(
                'producto_id' => 0,
                'nombre'      => "No hay productos en esta categoría",
                'marca'       => "",
                'categoria_id'=> $categoria_id,
                'precio'      => 0,
                'cantidad'    => 0,
                'descripcion' => ""
            ));
        }

        return $productos;
    } catch (PDOException $e) {
        return array(array(
            'producto_id' => 0,
            'nombre'      => "Error en BD: " . $e->getMessage(),
            'marca'       => "",
            'categoria_id'=> 0,
            'precio'      => 0,
            'cantidad'    => 0,
            'descripcion' => ""
        ));
    }
}

// Procesar petición SOAP
$POST_DATA = file_get_contents("php://input");
$server->service($POST_DATA);
exit();
?>
