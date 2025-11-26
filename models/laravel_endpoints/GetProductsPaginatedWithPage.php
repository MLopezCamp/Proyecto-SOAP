<?php
require_once __DIR__ . '/../../config/EnvLoader.php';
EnvLoader::load();
require_once "../../vendor/autoload.php";
require_once "../../vendor/econea/nusoap/src/nusoap.php";
require_once "../../models/middleware/TokenValidator.php";
require_once "../../models/middleware/RoleValidator.php";

$namespace = "GetProductsPaginatedWithPageSOAP";

$server = new nusoap_server();
$server->configureWSDL("SoapService", $namespace);

$server->wsdl->addComplexType(
    'PaginatedResponse',
    'complexType',
    'struct',
    'all',
    '',
    [
        'xml_data' => ['name' => 'xml_data', 'type' => 'xsd:string']
    ]
);

$server->register(
    "GetProductsPaginatedWithPageService",
    [
        "cantidad" => "xsd:int",
        "pagina" => "xsd:int"
    ],
    [
        "return" => "tns:PaginatedResponse"
    ],
    $namespace,
    false,
    "rpc",
    "encoded",
    "Obtiene productos paginados con número de página"
);

function GetProductsPaginatedWithPageService($cantidad, $pagina)
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
        '/api/productos', 
        'GET'
    );
    
    if (!$permisoCheck['permitido']) {
        return ["xml_data" => "<error>{$permisoCheck['mensaje']}</error>"];
    }

    // URL CORREGIDA - Usar 'paginacion-avanzada' como está en api.php
    $url = "http://localhost:8000/api/productos/paginacion-avanzada?page=$pagina&cantidad=$cantidad";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
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
        return ["xml_data" => "<error>Laravel devolvió código $http. URL: $url</error>"];
    }

    $json = json_decode($response, true);
    
    if (!$json) {
        return ["xml_data" => "<error>Respuesta JSON inválida</error>"];
    }

    $xml = "<paginacion>";
    $xml .= "<pagina_actual>{$json['current_page']}</pagina_actual>";
    $xml .= "<total>{$json['total']}</total>";
    $xml .= "<por_pagina>{$json['per_page']}</por_pagina>";
    $xml .= "<productos>";
    foreach ($json['data'] as $p) {
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