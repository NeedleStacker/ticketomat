-- ======================================================
--  Support Ticketing System - Database Schema
--  Database: liveinsb_tickets_db
--  Collation: utf8_croatian_ci
-- ======================================================

CREATE DATABASE IF NOT EXISTS `liveinsb_tickets_db`
  DEFAULT CHARACTER SET utf8
  COLLATE utf8_croatian_ci;

USE `liveinsb_tickets_db`;

-- ======================================================
-- Table: users
-- ======================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(150) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('client','agent','admin') NOT NULL DEFAULT 'client',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_croatian_ci;

-- ======================================================
-- Table: tickets
-- ======================================================
DROP TABLE IF EXISTS `tickets`;
CREATE TABLE `tickets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `assigned_to` INT UNSIGNED NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `device_name` VARCHAR(255) NULL,
  `serial_number` VARCHAR(255) NULL,
  `priority` ENUM('low','medium','high','urgent') DEFAULT 'medium',
  `status` VARCHAR(255) DEFAULT 'Otvoren',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `canceled_at` DATETIME NULL,
  `cancel_reason` TEXT NULL,
  `request_creator` VARCHAR(255) NULL,
  `creator_contact` VARCHAR(255) NULL,
  `attachment` LONGBLOB NULL,
  `attachment_name` VARCHAR(255) NULL,
  `attachment_type` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tickets_user` (`user_id`),
  KEY `fk_tickets_agent` (`assigned_to`),
  CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tickets_agent` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_croatian_ci;

-- ======================================================
-- Table: ticket_comments
-- ======================================================
DROP TABLE IF EXISTS `ticket_comments`;
CREATE TABLE `ticket_comments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `comment` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_comment_ticket` (`ticket_id`),
  KEY `fk_comment_user` (`user_id`),
  CONSTRAINT `fk_comment_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_croatian_ci;

-- ======================================================
-- Table: devices
-- ======================================================
CREATE TABLE IF NOT EXISTS `devices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL UNIQUE
);

-- ======================================================
-- Table: api_keys
-- ======================================================
DROP TABLE IF EXISTS `api_keys`;
CREATE TABLE `api_keys` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key_value` VARCHAR(255) NOT NULL UNIQUE,
  `label` VARCHAR(100),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_croatian_ci;

-- ======================================================
-- Initial Data
-- ======================================================
-- Default admin user (password: admin)
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`) VALUES
('admin', 'admin@example.com', '$2y$10$CkWphtLjvKZ0zQbNn6Q4i.eIt8ZTvslzEcwZbQG7A0E3P4Wj2WkhW', 'admin');

-- Default devices
INSERT INTO `devices` (name) VALUES
('Ulrich CT Motion'),
('Ulrich MAX2/3'),
('Vernacare Vortex AIR'),
('Vernacare Vortex+'),
('ACIST CVi'),
('Eurosets ECMOLIFE');
