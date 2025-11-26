<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../vendor/econea/nusoap/src/nusoap.php";
require_once __DIR__ . "/../config/Database.php";
require_once __DIR__ . "/JwtHelper.php";

$namespace = "LoginUserSOAP";

$server = new nusoap_server();
$server->configureWSDL("LoginUserService", $namespace);

// Tipo de respuesta
$server->wsdl->addComplexType(
    'LoginResponse',
    'complexType',
    'struct',
    'all',
    '',
    [
        'estado'     => ['name' => 'estado', 'type' => 'xsd:string'],
        'mensaje'    => ['name' => 'mensaje', 'type' => 'xsd:string'],
        'token'      => ['name' => 'token', 'type' => 'xsd:string'],
        'id_usuario' => ['name' => 'id_usuario', 'type' => 'xsd:int'],
        'id_rol'     => ['name' => 'id_rol', 'type' => 'xsd:int'],
        'correo'     => ['name' => 'correo', 'type' => 'xsd:string']
    ]
);

// Registrar método
$server->register(
    "loginUser",
    [
        'correo'   => 'xsd:string',
        'password' => 'xsd:string'
    ],
    ['return' => 'tns:LoginResponse'],
    $namespace
);

function loginUser($correo, $password)
{
    try {
        $db = new Database();
        $conn = $db->getConnection();

        $sql = "SELECT user_id, usu_correo, password, rol_id
                FROM users
                WHERE usu_correo = :correo
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            return [
                'estado' => 'ERROR',
                'mensaje' => 'El correo no está registrado.',
                'token' => '',
                'id_usuario' => 0,
                'id_rol' => 0,
                'correo' => ''
            ];
        }

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($password !== $usuario['password']) {
            return [
                'estado' => 'ERROR',
                'mensaje' => 'Contraseña incorrecta.',
                'token' => '',
                'id_usuario' => 0,
                'id_rol' => 0,
                'correo' => ''
            ];
        }

        // Generar token
        $token = JwtHelper::generarToken($usuario);

        return [
            'estado' => 'OK',
            'mensaje' => 'Inicio de sesión exitoso.',
            'token' => $token,
            'id_usuario' => (int)$usuario['user_id'],
            'id_rol' => (int)$usuario['rol_id'],
            'correo' => $usuario['usu_correo']
        ];

    } catch (Exception $e) {
        return [
            'estado' => 'ERROR',
            'mensaje' => $e->getMessage(),
            'token' => '',
            'id_usuario' => 0,
            'id_rol' => 0,
            'correo' => ''
        ];
    }
}

$server->service(file_get_contents("php://input"));
exit;
