
CREATE TABLE `_magrathea_config` (
	`id` int(11) PRIMARY KEY AUTO_INCREMENT,
	`name` varchar(255) UNIQUE,
	`value` varchar(255) DEFAULT NULL,
	`is_system` BOOLEAN NULL DEFAULT FALSE,
	`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ,
	`updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `_magrathea_roles` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(255) DEFAULT NULL,
	`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ,
	`updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
);

INSERT INTO `_magrathea_roles`
( `name` ) VALUES ( "super_admin" );

CREATE TABLE `_magrathea_users` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`email` varchar(255) UNIQUE,
	`password` varchar(255) DEFAULT NULL,
	`last_login` timestamp,
	`role_id` int(11) NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
	`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ,
	`updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
);

CREATE TABLE `_magrathea_logs` (
	`id` bigint(11) unsigned NOT NULL AUTO_INCREMENT,
	`user_id` int(11) NOT NULL,
	`action` varchar(255) NOT NULL,
	`victim` varchar(255) NULL,
	`info` text DEFAULT NULL,
	`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ,
	`updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
);

