-- Practical 12: safe update for the existing studenthub.events table.
-- BACK UP the studenthub database in phpMyAdmin before running this script.
-- Do NOT drop or recreate events, students, or registrations.
USE studenthub;

ALTER TABLE events
    ADD COLUMN description TEXT NULL,
    ADD COLUMN poster VARCHAR(255) NULL,
    ADD COLUMN status ENUM('Open','Closed') NOT NULL DEFAULT 'Open';

-- Existing event records remain intact. Set existing records to Open by default.
