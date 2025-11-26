<?php

class Database
{
    private $host = "127.0.0.1";
    private $port = "3306";
    private $db_name = "proyecto";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name}";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("Error de conexión a BD: " . $e->getMessage(), 3, __DIR__ . '/php_errors.log');
        }

        return $this->conn;
    }
}
