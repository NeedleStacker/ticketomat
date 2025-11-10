-- Kreiranje tablice za podatke o tvrtki
CREATE TABLE IF NOT EXISTS `company_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `oib` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `logo` longblob,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dodavanje stupaca u users tablicu
ALTER TABLE `users`
ADD COLUMN `first_name` VARCHAR(100) DEFAULT NULL AFTER `role`,
ADD COLUMN `last_name` VARCHAR(100) DEFAULT NULL AFTER `first_name`;

-- Ažuriranje postojećeg korisnika
UPDATE `users` SET `last_name` = 'Korisnik', `first_name` = 'Test' WHERE `username` = 'klijent';

-- Brisanje `api_keys` tablice jer se više ne koristi
DROP TABLE IF EXISTS `api_keys`;
