CREATE USER app IDENTIFIED BY 'gomix-App';
CREATE DATABASE app;
GRANT ALL ON app.* TO app;
ALTER DATABASE `app` COLLATE utf8mb4_general_ci;
use `app`;
CREATE TABLE `view` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ip` varchar(64) NULL,
  `mip` varchar(64) NULL,
  `server_name` text NULL,
  `host` text NULL,
  `agent` text NULL,
  `note` text NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='保存浏览者基本信息';
set global time_zone = '+8:00';