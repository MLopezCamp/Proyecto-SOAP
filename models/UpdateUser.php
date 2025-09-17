<?php
// Configuración del servidor SOAP
require_once "../vendor/econea/nusoap/src/nusoap.php";
require_once "../config/Database.php";

$namespace = "UpdateUserSOAP";
$server = new soap_server();
$server->configureWSDL('SoapService', $namespace);

// Definición de parámetros
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

// Registrar método
$server->register(
    'UpdateUserService',
    array('data' => 'tns:UpdateUser'),
    array('return' => 'xsd:string'),
    $namespace,
    false,
    'rpc',
    'encoded',
    'Actualizar un usuario'
);

// Función de actualización
function UpdateUserService($data) {
    global $pdo;
    if (!$pdo) return "Error: Conexión a BD no disponible";

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

        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':user_name', $data['user_name']);
        $stmt->bindParam(':lastname', $data['lastname']);
        $stmt->bindParam(':doc_type_id', $data['doc_type_id']);
        $stmt->bindParam(':usu_correo', $data['usu_correo']);
        $stmt->bindParam(':num_doc', $data['num_doc']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':phone', $data['phone']);

        $stmt->execute();

        return $stmt->rowCount() > 0 ? "Usuario actualizado correctamente" : "No se encontró el usuario";
    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}

// Procesamiento SOAP
$POST_DATA = file_get_contents("php://input");
$server->service($POST_DATA);
exit();
?>
