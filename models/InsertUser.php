<?php
require_once "../vendor/autoload.php";
require_once "../vendor/econea/nusoap/src/nusoap.php";
require_once "../config/Database.php";
require_once "../models/middleware/TokenValidator.php";

$namespace = "InsertUserSOAP";
$server = new soap_server();
$server->configureWSDL('SoapService', $namespace);

// Tipo para Insertar Usuario
$server->wsdl->addComplexType(
    'InsertUser',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'user_name'   => array('name' => 'user_name', 'type' => 'xsd:string'),
        'lastname'    => array('name' => 'lastname', 'type' => 'xsd:string'),
        'doc_type_id' => array('name' => 'doc_type_id', 'type' => 'xsd:int'),
        'usu_correo'  => array('name' => 'usu_correo', 'type' => 'xsd:string'),
        'password'    => array('name' => 'password', 'type' => 'xsd:string'),
        'num_doc'     => array('name' => 'num_doc', 'type' => 'xsd:string'),
        'address'     => array('name' => 'address', 'type' => 'xsd:string'),
        'phone'       => array('name' => 'phone', 'type' => 'xsd:string'),
        'id_rol'      => array('name' => 'id_rol', 'type' => 'xsd:int')
    )
);

// Registrar método
$server->register(
    'InsertUserService',
    array('data' => 'tns:InsertUser'),
    array('return' => 'xsd:string'),
    $namespace,
    false,
    'rpc',
    'encoded',
    'Insertar un usuario'
);

function InsertUserService($data) {

    // 1. Leer token
    $token = TokenValidator::extractToken();
    $valid = TokenValidator::validate($token);

    if (!$valid['ok']) {
        return "<error>{$valid['mensaje']}</error>";
    }

    // 2. Conexión BD
    $db = new Database();
    $pdo = $db->getConnection();
    if (!$pdo) return "Error: Conexión no disponible";

    try {

        $hashed = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("INSERT INTO users
            (user_name, lastname, doc_type_id, usu_correo, password, num_doc, address, phone, id_rol, created_date)
            VALUES (:user_name, :lastname, :doc_type_id, :usu_correo, :password, :num_doc, :address, :phone, :id_rol, NOW())");

        $stmt->bindParam(':user_name', $data['user_name']);
        $stmt->bindParam(':lastname', $data['lastname']);
        $stmt->bindParam(':doc_type_id', $data['doc_type_id']);
        $stmt->bindParam(':usu_correo', $data['usu_correo']);
        $stmt->bindParam(':password', $hashed);
        $stmt->bindParam(':num_doc', $data['num_doc']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':id_rol', $data['id_rol']);

        $stmt->execute();

        return "Usuario insertado correctamente";

    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}

$POST_DATA = file_get_contents("php://input");
$server->service($POST_DATA);
exit();
