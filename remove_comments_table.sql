-- This script removes the legacy ticket_comments table, which is no longer needed after migrating to the Cusdis commenting system.
DROP TABLE IF EXISTS `ticket_comments`;
