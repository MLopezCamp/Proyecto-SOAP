<?php
require_once "../vendor/autoload.php";
require_once "../vendor/econea/nusoap/src/nusoap.php";
require_once "../config/Database.php";
require_once "../models/middleware/TokenValidator.php";
require_once "../models/middleware/RoleMiddleware.php";

$namespace = "UpdateUserSOAP";
$server = new soap_server();
$server->configureWSDL('SoapService', $namespace);

/*
|---------------------------------------------------------
| DEFINICIÓN DEL TIPO COMPLEJO UpdateUser
|---------------------------------------------------------
*/
$server->wsdl->addComplexType(
    'UpdateUser',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'user_id'    => array('name' => 'user_id', 'type' => 'xsd:int'),
        'user_name'  => array('name' => 'user_name', 'type' => 'xsd:string'),
        'lastname'   => array('name' => 'lastname', 'type' => 'xsd:string'),
        'doc_type_id'=> array('name' => 'doc_type_id', 'type' => 'xsd:int'),
        'usu_correo' => array('name' => 'usu_correo', 'type' => 'xsd:string'),
        'num_doc'    => array('name' => 'num_doc', 'type' => 'xsd:string'),
        'address'    => array('name' => 'address', 'type' => 'xsd:string'),
        'phone'      => array('name' => 'phone', 'type' => 'xsd:string')
    )
);

/*
|---------------------------------------------------------
| REGISTRO DEL MÉTODO SOAP
|---------------------------------------------------------
*/
$server->register(
    'UpdateUserService',
    array('data' => 'tns:UpdateUser'),
    array('return' => 'xsd:string'),
    $namespace
);

/*
|---------------------------------------------------------
| IMPLEMENTACIÓN DEL SERVICIO
|---------------------------------------------------------
*/
function UpdateUserService($data)
{
    // 1) Obtener token del header SOAP
    $headers = apache_request_headers();
    $token = $headers['Authorization'] ?? "";

    if (!$token) {
        return "ERROR: Token no proporcionado";
    }

    // 2) Validar token
    $valid = TokenValidator::validate($token);
    if (!$valid['ok']) {
        return "ERROR: " . $valid['mensaje'];
    }

    $roleId = $valid['role'];
    $userIdToken = $valid['id'];

    // 3) Validar rol contra tb_routes + rutas_roles
    $rutaActual = "/soap/UpdateUser";
    $metodo = "POST";

    $roleCheck = RoleMiddleware::validateAccess($roleId, $rutaActual, $metodo);

    if (!$roleCheck['ok']) {
        return "ERROR: " . $roleCheck['mensaje'];
    }

    // 4) Conexión a BD (como tú la tenías)
    global $pdo;
    if (!$pdo) return "Error: Conexión a BD no disponible";

    if (!$pdo) {
        return "ERROR: Conexión a la BD no disponible";
    }

    // 5) Actualizar usuario
    try {
        $stmt = $pdo->prepare("UPDATE users
                               SET user_name = :user_name,
                                   lastname = :lastname,
                                   doc_type_id = :doc_type_id,
                                   usu_correo = :usu_correo,
                                   num_doc = :num_doc,
                                   address = :address,
                                   phone = :phone
                               WHERE user_id = :user_id");

        $stmt->execute([
            ':user_id'     => $data['user_id'],
            ':user_name'   => $data['user_name'],
            ':lastname'    => $data['lastname'],
            ':doc_type_id' => $data['doc_type_id'],
            ':usu_correo'  => $data['usu_correo'],
            ':num_doc'     => $data['num_doc'],
            ':address'     => $data['address'],
            ':phone'       => $data['phone'],
        ]);

        return $stmt->rowCount() > 0 ? 
            "Usuario actualizado correctamente" : 
            "No se encontró el usuario";

    } catch (PDOException $e) {
        return "ERROR: " . $e->getMessage();
    }
}

$server->service(file_get_contents("php://input"));
exit;
?>
