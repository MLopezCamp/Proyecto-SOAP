<?php
require_once __DIR__ . '/../../config/EnvLoader.php';
EnvLoader::load();
require_once "../../vendor/autoload.php";
require_once "../../vendor/econea/nusoap/src/nusoap.php";
require_once "../../models/middleware/TokenValidator.php";
require_once "../../models/middleware/RoleValidator.php";

$namespace = "CreateProductoSOAP";

$server = new nusoap_server();
$server->configureWSDL("CreateProductoService", $namespace);

$server->wsdl->addComplexType(
    'CreateProductoResponse',
    'complexType',
    'struct',
    'all',
    '',
    [
        'xml_data' => ['name' => 'xml_data', 'type' => 'xsd:string']
    ]
);

$server->register(
    "CreateProducto",
    [
        'nombre' => 'xsd:string',
        'precio' => 'xsd:float',
        'stock' => 'xsd:int',
        'categoria_id' => 'xsd:int'
    ],
    [
        "return" => "tns:CreateProductoResponse"
    ],
    $namespace,
    false,
    "rpc",
    "encoded",
    "Crea un producto en Laravel"
);

function CreateProducto($nombre, $precio, $stock, $categoria_id)
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
        'POST'
    );
    
    if (!$permisoCheck['permitido']) {
        return ["xml_data" => "<error>{$permisoCheck['mensaje']}</error>"];
    }

    $url = "http://localhost:8000/api/productos";
    $payload = json_encode([
        "nombre" => $nombre,
        "precio" => $precio,
        "stock" => $stock,
        "categoria_id" => $categoria_id,
        "estado" => "A"
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
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

    if ($http !== 201) {
        $errorData = json_decode($response, true);
        $errorMessage = "Laravel retornó código $http";
        
        if ($http === 422 && isset($errorData['errors'])) {
            $errorMessage .= " - Errores de validación: ";
            foreach ($errorData['errors'] as $field => $messages) {
                $errorMessage .= "$field: " . implode(', ', $messages) . "; ";
            }
        } elseif (isset($errorData['message'])) {
            $errorMessage .= " - " . $errorData['message'];
        }
        
        return ["xml_data" => "<error>" . htmlspecialchars($errorMessage) . "</error>"];
    }

    $data = json_decode($response, true);
    $xml = "<producto>";
    $xml .= "<id>{$data['id']}</id>";
    $xml .= "<nombre>" . htmlspecialchars($data['nombre']) . "</nombre>";
    $xml .= "<precio>{$data['precio']}</precio>";
    $xml .= "<stock>{$data['stock']}</stock>";
    $xml .= "<categoria_id>{$data['categoria_id']}</categoria_id>";
    $xml .= "<estado>" . htmlspecialchars($data['estado']) . "</estado>";
    $xml .= "</producto>";

    return ["xml_data" => $xml];
}

$server->service(file_get_contents("php://input"));
exit;