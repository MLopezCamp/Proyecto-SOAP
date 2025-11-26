<?php
require_once __DIR__ . '/../../config/EnvLoader.php';
EnvLoader::load();
require_once "../../vendor/autoload.php";
require_once "../../vendor/econea/nusoap/src/nusoap.php";
require_once "../../models/middleware/TokenValidator.php";
require_once "../../models/middleware/RoleValidator.php";

$namespace = "GetProductoSOAP";

$server = new nusoap_server();
$server->configureWSDL("GetProductoService", $namespace);

$server->wsdl->addComplexType(
    'GetProductoResponse',
    'complexType',
    'struct',
    'all',
    '',
    [
        'xml_data' => ['name' => 'xml_data', 'type' => 'xsd:string']
    ]
);

$server->register(
    "GetProducto",
    ['id' => 'xsd:int'],
    ["return" => "tns:GetProductoResponse"],
    $namespace
);

function GetProducto($id)
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

    // Llamar API Laravel
    $url = "http://localhost:8000/api/productos/$id";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Authorization: Bearer $token"
        ]
    ]);

    $response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ["xml_data" => "<error>Error CURL: $error</error>"];
    }

    if ($http !== 200) {
        return ["xml_data" => "<error>Laravel retornó código $http</error>"];
    }

    $p = json_decode($response, true);
    
    // Construir XML
    $xml = "<producto>";
    $xml .= "<id>{$p['id']}</id>";
    $xml .= "<nombre>" . htmlspecialchars($p['nombre']) . "</nombre>";
    $xml .= "<precio>{$p['precio']}</precio>";
    $xml .= "<stock>{$p['stock']}</stock>";
    $xml .= "<categoria>" . htmlspecialchars($p['categoria']['nombre'] ?? '') . "</categoria>";
    $xml .= "</producto>";

    return ["xml_data" => $xml];
}

$server->service(file_get_contents("php://input"));
exit;