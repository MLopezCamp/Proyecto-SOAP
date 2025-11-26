<?php
require_once __DIR__ . '/../../config/Database.php';

class RoleValidator
{
    /**
     * Valida si un rol tiene acceso a una ruta específica consultando la BD
     * 
     * @param int $rolId - ID del rol del usuario
     * @param string $ruta - Ruta a validar (ejemplo: "/api/productos")
     * @param string $metodo - Método HTTP (GET, POST, PUT, DELETE)
     * @return array
     */
    public static function validateAccess($rolId, $ruta, $metodo)
    {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            if (!$conn) {
                return [
                    'permitido' => false,
                    'mensaje' => 'Error de conexión a la base de datos'
                ];
            }

            // Consulta para verificar si el rol tiene acceso a esta ruta
            $sql = "SELECT rr.id_rr
                    FROM rutas_roles rr
                    INNER JOIN tb_routes r ON rr.id_ruta = r.id_ruta
                    WHERE rr.id_rol = :rol_id
                    AND r.ruta = :ruta
                    AND r.metodo = :metodo
                    LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':rol_id', $rolId, PDO::PARAM_INT);
            $stmt->bindParam(':ruta', $ruta, PDO::PARAM_STR);
            $stmt->bindParam(':metodo', $metodo, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return [
                    'permitido' => true,
                    'mensaje' => 'Acceso permitido'
                ];
            }

            return [
                'permitido' => false,
                'mensaje' => 'No tiene permisos para realizar esta operación. Contacte al administrador.'
            ];

        } catch (Exception $e) {
            return [
                'permitido' => false,
                'mensaje' => 'Error al validar permisos: ' . $e->getMessage()
            ];
        }
    }
}