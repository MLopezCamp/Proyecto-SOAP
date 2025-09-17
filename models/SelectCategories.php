<?php
//Servicio SOAP para listar categorías
require_once "../vendor/econea/nusoap/src/nusoap.php";
require_once "../config/Database.php";

$namespace = "SelectCategoriesSOAP";
$server = new soap_server();
$server->configureWSDL('SoapService', $namespace);

// Definir tipo de salida (categoría)
$server->wsdl->addComplexType(
    'Category',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'categoria_id' => array('name' => 'categoria_id', 'type' => 'xsd:int'),
        'nombre'       => array('name' => 'nombre', 'type' => 'xsd:string'),
        'estado'       => array('name' => 'estado', 'type' => 'xsd:string')
    )
);

// Definir array de categorías
$server->wsdl->addComplexType(
    'CategoryArray',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Category[]')
    ),
    'tns:Category'
);

// Registrar método
$server->register(
    'SelectCategoriesService',
    array(),
    array('return' => 'tns:CategoryArray'),
    $namespace,
    false,
    'rpc',
    'encoded',
    'Listar todas las categorías'
);

// Implementación del servicio
function SelectCategoriesService() {
    global $pdo;
    if (!$pdo) return array();

    try {
        $stmt = $pdo->query("SELECT categoria_id, nombre, estado FROM tb_categoria");
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$categorias) {
            return array(
                array(
                    'categoria_id' => 0,
                    'nombre'       => "No hay categorías registradas",
                    'estado'       => ""
                )
            );
        }

        return $categorias;
    } catch (PDOException $e) {
        return array(
            array(
                'categoria_id' => 0,
                'nombre'       => "Error en BD: " . $e->getMessage(),
                'estado'       => ""
            )
        );
    }
}

// Procesar petición SOAP
$POST_DATA = file_get_contents("php://input");
$server->service($POST_DATA);
exit();
?>
