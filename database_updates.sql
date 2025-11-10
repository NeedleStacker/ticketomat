
-- Drop the table if it exists to start fresh
DROP TABLE IF EXISTS `company_info`;

-- Create the company_info table
CREATE TABLE `company_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_croatian_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8_croatian_ci DEFAULT NULL,
  `oib` varchar(20) COLLATE utf8_croatian_ci DEFAULT NULL,
  `bank_account` varchar(50) COLLATE utf8_croatian_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8_croatian_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_croatian_ci DEFAULT NULL,
  `logo_path` varchar(255) COLLATE utf8_croatian_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_croatian_ci;

-- Insert a single row with default empty values so the UPDATE query works
INSERT INTO `company_info` (`id`, `name`, `address`, `oib`, `bank_account`, `phone`, `email`, `logo_path`) VALUES
(1, '', '', '', '', '', '', '');

ALTER TABLE `tickets` ADD COLUMN `creator_contact` VARCHAR(255) DEFAULT NULL;
