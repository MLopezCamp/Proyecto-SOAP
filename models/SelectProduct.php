<?php
// Servicio SOAP para listar productos
require_once "../vendor/econea/nusoap/src/nusoap.php";
require_once "../config/Database.php";

$namespace = "SelectProductSOAP";
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
        'categoria'   => array('name' => 'categoria', 'type' => 'xsd:string'),
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
    'SelectProductService',
    array(),
    array('return' => 'tns:ProductArray'),
    $namespace,
    false,
    'rpc',
    'encoded',
    'Listar todos los productos activos'
);

// Implementación del servicio
function SelectProductService() {
    global $pdo;
    if (!$pdo) return array();

    try {
        $stmt = $pdo->prepare("SELECT p.producto_id, p.nombre, p.marca, c.nombre AS categoria,
                                      p.precio, p.cantidad, p.descripcion
                               FROM tb_producto p
                               INNER JOIN tb_categoria c ON p.categoria_id = c.categoria_id
                               WHERE p.deleted = 0 AND c.estado = 'Activo'");
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$productos) {
            return array(
                array(
                    'producto_id' => 0,
                    'nombre'      => "No hay productos disponibles",
                    'marca'       => "",
                    'categoria'   => "",
                    'precio'      => 0,
                    'cantidad'    => 0,
                    'descripcion' => ""
                )
            );
        }

        return $productos;
    } catch (PDOException $e) {
        return array(
            array(
                'producto_id' => 0,
                'nombre'      => "Error en BD: " . $e->getMessage(),
                'marca'       => "",
                'categoria'   => "",
                'precio'      => 0,
                'cantidad'    => 0,
                'descripcion' => ""
            )
        );
    }
}

// Procesar petición SOAP
$POST_DATA = file_get_contents("php://input");
$server->service($POST_DATA);
exit();
?>
