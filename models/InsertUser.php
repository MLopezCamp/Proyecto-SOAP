<?php
require_once "../vendor/econea/nusoap/src/nusoap.php";
require_once "../config/Database.php";

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
        'num_doc'     => array('name' => 'num_doc', 'type' => 'xsd:string'),
        'address'     => array('name' => 'address', 'type' => 'xsd:string'),
        'phone'       => array('name' => 'phone', 'type' => 'xsd:string')
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
    global $pdo;

    if (!$pdo) {
        return "Error: Conexión no disponible";
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO users 
            (user_name, lastname, doc_type_id, usu_correo, num_doc, address, phone, created_date)
            VALUES (:user_name, :lastname, :doc_type_id, :usu_correo, :num_doc, :address, :phone, NOW())");

        $stmt->bindParam(':user_name', $data['user_name']);
        $stmt->bindParam(':lastname', $data['lastname']);
        $stmt->bindParam(':doc_type_id', $data['doc_type_id']);
        $stmt->bindParam(':usu_correo', $data['usu_correo']);
        $stmt->bindParam(':num_doc', $data['num_doc']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':phone', $data['phone']);

        $stmt->execute();
        return "Se ha guardado correctamente";
    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}

$POST_DATA = file_get_contents("php://input");
$server->service($POST_DATA);
exit;