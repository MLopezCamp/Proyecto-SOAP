<?php
require_once __DIR__ . '/../../config/EnvLoader.php';
EnvLoader::load();
require_once "../../vendor/autoload.php";
require_once "../../vendor/econea/nusoap/src/nusoap.php";
require_once "../../models/middleware/TokenValidator.php";
require_once "../../models/middleware/RoleValidator.php";

$namespace = "UpdateStockSOAP";

$server = new nusoap_server();
$server->configureWSDL("SoapService", $namespace);

$server->wsdl->addComplexType(
    'StockResponse',
    'complexType',
    'struct',
    'all',
    '',
    [
        "xml_data" => ["name" => "xml_data", "type" => "xsd:string"]
    ]
);

$server->register(
    "UpdateStockService",
    [
        "id" => "xsd:int",
        "cantidad" => "xsd:int"
    ],
    [
        "return" => "tns:StockResponse"
    ],
    $namespace,
    false,
    "rpc",
    "encoded",
    "Actualiza el stock de un producto en Laravel"
);

function UpdateStockService($id, $cantidad)
{
    // Extraer token del header
    $token = TokenValidator::extractToken();
    
    if (!$token) {
        return ["xml_data" => "<error>Token no proporcionado en el header Authorization</error>"];
    }
    
    // Validar token
    $valid = TokenValidator::validate($token);
    if ($valid["estado"] === "ERROR") {
        return ["xml_data" => "<error>{$valid['mensaje']}</error>"];
    }

    // Validar permisos consultando BD
    $permisoCheck = RoleValidator::validateAccess(
    $valid['rol_id'], 
    '/api/productos/actualizar-stock',
    'PUT'
);
    
    if (!$permisoCheck['permitido']) {
        return ["xml_data" => "<error>{$permisoCheck['mensaje']}</error>"];
    }

    // URL CORREGIDA
    $url = "http://localhost:8000/api/productos/$id/actualizar-stock";
    $payload = json_encode(["cantidad" => $cantidad]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Bearer $token"
        ]
    ]);

    $response = curl_exec($ch);
    $error = curl_errno($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        return ["xml_data" => "<error>Error CURL</error>"];
    }

    if ($http != 200) {
        return ["xml_data" => "<error>Laravel devolvió código $http</error>"];
    }

    $json = json_decode($response, true);

    $xml = "<resultado>";
    $xml .= "<mensaje>{$json['message']}</mensaje>";
    $xml .= "<nuevo_stock>{$json['nuevo_stock']}</nuevo_stock>";
    $xml .= "</resultado>";

    return ["xml_data" => $xml];
}

$server->service(file_get_contents("php://input"));
exit;