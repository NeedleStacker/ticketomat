-- Disable foreign key checks to avoid errors when dropping columns.
SET FOREIGN_KEY_CHECKS=0;

-- Create the new table for ticket attachments
CREATE TABLE `ticket_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `attachment_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `attachment_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `attachment` longblob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `ticket_attachments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Drop the old attachment columns from the tickets table
ALTER TABLE `tickets`
  DROP COLUMN `attachment`,
  DROP COLUMN `attachment_name`,
  DROP COLUMN `attachment_type`;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;
