<?php
require_once __DIR__ . '/../../config/EnvLoader.php';
EnvLoader::load();
require_once "../../vendor/autoload.php";
require_once "../../vendor/econea/nusoap/src/nusoap.php";
require_once "../../models/middleware/TokenValidator.php";
require_once "../../models/middleware/RoleValidator.php";

$namespace = "UpdateProductoSOAP";

$server = new nusoap_server();
$server->configureWSDL("UpdateProductoService", $namespace);

$server->wsdl->addComplexType(
    'UpdateProductoResponse',
    'complexType',
    'struct',
    'all',
    '',
    [
        'xml_data' => ['name' => 'xml_data', 'type' => 'xsd:string']
    ]
);

$server->register(
    "UpdateProductoService",
    [
        'id' => 'xsd:int',
        'nombre' => 'xsd:string',
        'categoria_id' => 'xsd:int',
        'stock' => 'xsd:int',
        'precio' => 'xsd:decimal',
        'estado' => 'xsd:string'
    ],
    [
        "return" => "tns:UpdateProductoResponse"
    ],
    $namespace,
    false,
    "rpc",
    "encoded",
    "Actualiza un producto en Laravel vía SOAP"
);

function UpdateProductoService($id, $nombre, $categoria_id, $stock, $precio, $estado)
{
    // Extraer token del header
    $token = TokenValidator::extractToken();
    
    if (!$token) {
        return ["xml_data" => "<error>Token no proporcionado en el header Authorization</error>"];
    }
    
    // Validación token
    $validacion = TokenValidator::validate($token);
    if ($validacion["estado"] === "ERROR") {
        return ["xml_data" => "<error>{$validacion['mensaje']}</error>"];
    }

    // Validar permisos consultando BD
    $permisoCheck = RoleValidator::validateAccess(
        $validacion['rol_id'], 
        '/api/productos/:id', 
        'PUT'
    );
    
    if (!$permisoCheck['permitido']) {
        return ["xml_data" => "<error>{$permisoCheck['mensaje']}</error>"];
    }

    $url = "http://localhost:8000/api/productos/" . $id;
    $payload = json_encode([
        "nombre" => $nombre,
        "categoria_id" => $categoria_id,
        "stock" => $stock,
        "precio" => $precio,
        "estado" => $estado
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Accept: application/json",
        "Authorization: Bearer $token"
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        return ["xml_data" => "<error>Error conectando a Laravel: $err</error>"];
    }

    if ($http !== 200) {
        return ["xml_data" => "<error>Laravel retornó código $http</error>"];
    }

    $data = json_decode($response, true);
    if (!$data) {
        return ["xml_data" => "<error>Respuesta inválida desde Laravel</error>"];
    }

    $xml = "<resultado>";
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $xml .= "<$key>" . htmlspecialchars(json_encode($value)) . "</$key>";
        } else {
            $xml .= "<$key>" . htmlspecialchars($value) . "</$key>";
        }
    }
    $xml .= "</resultado>";

    return ["xml_data" => $xml];
}

$server->service(file_get_contents("php://input"));
exit;