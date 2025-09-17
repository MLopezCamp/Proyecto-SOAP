-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.4.3 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para proyecto
CREATE DATABASE IF NOT EXISTS `proyecto` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `proyecto`;

-- Volcando estructura para tabla proyecto.tb_categoria
CREATE TABLE IF NOT EXISTS `tb_categoria` (
  `categoria_id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `estado` enum('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`categoria_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla proyecto.tb_categoria: ~3 rows (aproximadamente)
DELETE FROM `tb_categoria`;
INSERT INTO `tb_categoria` (`categoria_id`, `nombre`, `estado`) VALUES
	(1, 'Alimentos', 'Activo'),
	(2, 'Electrodomésticos', 'Inactivo'),
	(3, 'Licor', 'Activo');

-- Volcando estructura para tabla proyecto.tb_producto
CREATE TABLE IF NOT EXISTS `tb_producto` (
  `producto_id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `categoria_id` int NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `cantidad` int NOT NULL,
  `descripcion` text,
  `deleted` tinyint(1) DEFAULT '0',
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`producto_id`),
  KEY `categoria_id` (`categoria_id`),
  CONSTRAINT `tb_producto_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `tb_categoria` (`categoria_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla proyecto.tb_producto: ~6 rows (aproximadamente)
DELETE FROM `tb_producto`;
INSERT INTO `tb_producto` (`producto_id`, `nombre`, `marca`, `categoria_id`, `precio`, `cantidad`, `descripcion`, `deleted`, `created_date`) VALUES
	(1, 'ChocoMani', 'Jumbo', 1, 233.00, 2, 'Chocolatina', 1, '2025-09-07 19:39:21'),
	(2, 'Arroz ', 'Roa', 1, 17000.00, 0, 'Arroz de alta calidad', 0, '2025-09-07 19:53:22'),
	(3, 'Nevera', 'Whirpool', 2, 2500000.00, 5, 'Nevera inteligente', 0, '2025-09-07 19:55:38'),
	(4, 'Microondas', 'LG', 2, 800000.00, 5, 'Microondas inteligente', 0, '2025-09-07 19:57:00'),
	(5, 'Whisky', 'OlD PARR', 3, 150000.00, 15, 'Botella de whisky', 0, '2025-09-07 19:57:47'),
	(6, 'Aguardiente', 'Amarillo de manzanares', 3, 70000.00, 40, 'Botella de aguardiente', 0, '2025-09-07 19:58:26');

-- Volcando estructura para tabla proyecto.users
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `doc_type_id` int DEFAULT NULL,
  `usu_correo` varchar(50) DEFAULT NULL,
  `num_doc` bigint DEFAULT NULL,
  `address` varchar(50) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla proyecto.users: ~0 rows (aproximadamente)
DELETE FROM `users`;
INSERT INTO `users` (`user_id`, `user_name`, `lastname`, `doc_type_id`, `usu_correo`, `num_doc`, `address`, `phone`, `created_date`) VALUES
	(1, 'Mauricio', 'Lopez', 1, 'lopez@gmail.com', 5675, 'calle 12', '4563634676', '2025-09-07 13:52:24');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
