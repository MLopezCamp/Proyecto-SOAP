<?php
require_once __DIR__ . '/../../config/EnvLoader.php';
EnvLoader::load();
require_once "../../vendor/autoload.php";
require_once "../../vendor/econea/nusoap/src/nusoap.php";
require_once "../../models/middleware/TokenValidator.php";
require_once "../../models/middleware/RoleValidator.php";

$namespace = "SearchProductoSOAP";

$server = new nusoap_server();
$server->configureWSDL("SoapService", $namespace);

$server->wsdl->addComplexType(
    'SearchResponse',
    'complexType',
    'struct',
    'all',
    '',
    [
        "xml_data" => ["name" => "xml_data", "type" => "xsd:string"]
    ]
);

$server->register(
    "SearchProductoService",
    [
        "nombre" => "xsd:string"
    ],
    [
        "return" => "tns:SearchResponse"
    ],
    $namespace,
    false,
    "rpc",
    "encoded",
    "Busca productos por nombre en Laravel"
);

function SearchProductoService($nombre)
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

    $url = "http://localhost:8000/api/productos/buscar?nombre=" . urlencode($nombre);

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
        return ["xml_data" => "<error>Laravel devolvió código $http</error>"];
    }

    $json = json_decode($response, true);

    $xml = "<productos>";
    foreach ($json as $p) {
        $xml .= "<producto>";
        $xml .= "<id>{$p['id']}</id>";
        $xml .= "<nombre>{$p['nombre']}</nombre>";
        $xml .= "<precio>{$p['precio']}</precio>";
        $xml .= "<stock>{$p['stock']}</stock>";
        $xml .= "<categoria>{$p['categoria']['nombre']}</categoria>";
        $xml .= "</producto>";
    }
    $xml .= "</productos>";

    return ["xml_data" => $xml];
}

$server->service(file_get_contents("php://input"));
exit;