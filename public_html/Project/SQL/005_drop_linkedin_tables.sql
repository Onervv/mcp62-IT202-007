TRUNCATE TABLE LinkedInProfiles;

ALTER TABLE LinkedInProfiles 
ADD COLUMN is_manual TINYINT(1) DEFAULT 0 
COMMENT 'Flag to distinguish between manual and API-created profiles';