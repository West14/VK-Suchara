-- --------------------------------------------------------
-- Хост:                         localhost
-- Версия сервера:               10.3.13-MariaDB-log - mariadb.org binary distribution
-- Операционная система:         Win64
-- HeidiSQL Версия:              10.1.0.5464
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Дамп структуры для таблица suchara.chat_user
CREATE TABLE IF NOT EXISTS `chat_user` (
  `user_id` int(11) NOT NULL,
  `peer_id` int(11) NOT NULL,
  `pidor_total` int(11) DEFAULT 0,
  `message_count` int(11) NOT NULL DEFAULT 0,
  `command_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`,`peer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Экспортируемые данные не выделены.
-- Дамп структуры для таблица suchara.command_log
CREATE TABLE IF NOT EXISTS `command_log` (
  `command_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `peer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `timestamp` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`command_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Экспортируемые данные не выделены.
-- Дамп структуры для таблица suchara.message_log
CREATE TABLE IF NOT EXISTS `message_log` (
  `message_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `peer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(10000) DEFAULT NULL,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Экспортируемые данные не выделены.
-- Дамп структуры для таблица suchara.pidor_log
CREATE TABLE IF NOT EXISTS `pidor_log` (
  `pidor_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `peer_id` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL DEFAULT unix_timestamp(),
  PRIMARY KEY (`pidor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Экспортируемые данные не выделены.
-- Дамп структуры для таблица suchara.user
CREATE TABLE IF NOT EXISTS `user` (
  `message_count` int(11) NOT NULL DEFAULT 0,
  `pidor_month` int(11) DEFAULT 0,
  `pidor_total` int(11) DEFAULT 0,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Экспортируемые данные не выделены.
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
