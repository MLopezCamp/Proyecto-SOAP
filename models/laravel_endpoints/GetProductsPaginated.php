<?php
require_once __DIR__ . '/../../config/EnvLoader.php';
EnvLoader::load();
require_once "../../vendor/autoload.php";
require_once "../../vendor/econea/nusoap/src/nusoap.php";
require_once "../../models/middleware/TokenValidator.php";
require_once "../../models/middleware/RoleValidator.php";

$namespace = "GetProductsPaginatedSOAP";

$server = new nusoap_server();
$server->configureWSDL("SoapService", $namespace);

$server->wsdl->addComplexType(
    'ProductPaginatedResponse',
    'complexType',
    'struct',
    'all',
    '',
    [
        'xml_data' => ['name' => 'xml_data', 'type' => 'xsd:string']
    ]
);

$server->register(
    "GetProductsPaginatedService",
    [
        'cantidad' => 'xsd:int'
    ],
    [
        "return" => "tns:ProductPaginatedResponse"
    ],
    $namespace,
    false,
    "rpc",
    "encoded",
    "Obtiene productos paginados desde Laravel"
);

function GetProductsPaginatedService($cantidad)
{
    // Extraer token del header
    $token = TokenValidator::extractToken();
    
    if (!$token) {
        return ["xml_data" => "<error>Token no proporcionado en el header Authorization</error>"];
    }
    
    // Validar token
    $validacion = TokenValidator::validate($token);
    if ($validacion["estado"] === "ERROR") {
        return ["xml_data" => "<error>{$validacion['mensaje']}</error>"];
    }

    // Validar permisos consultando BD
    $permisoCheck = RoleValidator::validateAccess(
        $validacion['rol_id'], 
        '/api/productos', 
        'GET'
    );
    
    if (!$permisoCheck['permitido']) {
        return ["xml_data" => "<error>{$permisoCheck['mensaje']}</error>"];
    }

    // PETICIÓN A LARAVEL
    $apiUrl = "http://localhost:8000/api/productos/paginacion?cantidad=" . intval($cantidad);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: application/json",
        "Authorization: Bearer $token"
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Errores de CURL
    if ($error) {
        return ["xml_data" => "<error>Error conectando a Laravel: $error</error>"];
    }

    // Validar HTTP
    if ($httpcode != 200) {
        return ["xml_data" => "<error>Laravel retornó código $httpcode</error>"];
    }

    // Decodificar JSON
    $data = json_decode($response, true);
    if ($data === NULL) {
        return ["xml_data" => "<error>JSON inválido recibido desde Laravel</error>"];
    }

    // JSON → XML
    $xml = "<paginacion>";
    $xml .= "<total>{$data['total']}</total>";
    $xml .= "<por_pagina>{$data['per_page']}</por_pagina>";
    $xml .= "<pagina_actual>{$data['current_page']}</pagina_actual>";
    $xml .= "<productos>";
    foreach ($data['data'] as $p) {
        $xml .= "<producto>";
        $xml .= "<id>{$p['id']}</id>";
        $xml .= "<nombre>" . htmlspecialchars($p['nombre']) . "</nombre>";
        $xml .= "<precio>{$p['precio']}</precio>";
        $xml .= "<stock>{$p['stock']}</stock>";
        $xml .= "<categoria>" . htmlspecialchars($p['categoria']['nombre'] ?? '') . "</categoria>";
        $xml .= "</producto>";
    }
    $xml .= "</productos>";
    $xml .= "</paginacion>";

    return ["xml_data" => $xml];
}

$server->service(file_get_contents("php://input"));
exit;