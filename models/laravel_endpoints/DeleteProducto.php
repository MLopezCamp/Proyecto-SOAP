<?php
require_once __DIR__ . '/../../config/EnvLoader.php';
EnvLoader::load();
require_once "../../vendor/autoload.php";
require_once "../../vendor/econea/nusoap/src/nusoap.php";
require_once "../../models/middleware/TokenValidator.php";
require_once "../../models/middleware/RoleValidator.php";

$namespace = "DeleteProductoSOAP";

$server = new nusoap_server();
$server->configureWSDL("DeleteProductoService", $namespace);

$server->wsdl->addComplexType(
    'DeleteProductoResponse',
    'complexType',
    'struct',
    'all',
    '',
    [
        'xml_data' => ['name' => 'xml_data', 'type' => 'xsd:string']
    ]
);

$server->register(
    "DeleteProductoService",
    [
        'id' => 'xsd:int'
    ],
    [
        "return" => "tns:DeleteProductoResponse"
    ],
    $namespace,
    false,
    "rpc",
    "encoded",
    "Elimina un producto en Laravel vía SOAP"
);

function DeleteProductoService($id)
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
        'DELETE'
    );
    
    if (!$permisoCheck['permitido']) {
        return ["xml_data" => "<error>{$permisoCheck['mensaje']}</error>"];
    }

    $url = "http://localhost:8000/api/productos/" . $id;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
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
    $xml .= "<mensaje>" . htmlspecialchars($data["message"] ?? "OK") . "</mensaje>";
    $xml .= "</resultado>";

    return ["xml_data" => $xml];
}

$server->service(file_get_contents("php://input"));
exit;