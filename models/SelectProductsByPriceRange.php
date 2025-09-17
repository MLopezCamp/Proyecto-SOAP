<?php
// Servicio SOAP para listar productos en un rango de precio
require_once "../vendor/econea/nusoap/src/nusoap.php";
require_once "../config/Database.php";

$namespace = "SelectProductsByPriceRangeSOAP";
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
    'SelectProductsByPriceRangeService',
    array('precio_min' => 'xsd:decimal', 'precio_max' => 'xsd:decimal'),
    array('return' => 'tns:ProductArray'),
    $namespace,
    false,
    'rpc',
    'encoded',
    'Listar productos en un rango de precio'
);

// Implementación
function SelectProductsByPriceRangeService($precio_min, $precio_max) {
    global $pdo;
    if (!$pdo) return array();

    if ($precio_min < 0 || $precio_max <= 0 || $precio_min > $precio_max) {
        return array(array(
            'producto_id' => 0,
            'nombre'      => "Error: rango de precios inválido",
            'marca'       => "",
            'categoria_id'=> 0,
            'precio'      => 0,
            'cantidad'    => 0,
            'descripcion' => ""
        ));
    }

    try {
        $stmt = $pdo->prepare("SELECT producto_id, nombre, marca, categoria_id, precio, cantidad, descripcion
                               FROM tb_producto
                               WHERE precio BETWEEN :min AND :max
                                 AND deleted = 0");
        $stmt->bindParam(':min', $precio_min);
        $stmt->bindParam(':max', $precio_max);
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$productos) {
            return array(array(
                'producto_id' => 0,
                'nombre'      => "No se encontraron productos en el rango de precio indicado",
                'marca'       => "",
                'categoria_id'=> 0,
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
