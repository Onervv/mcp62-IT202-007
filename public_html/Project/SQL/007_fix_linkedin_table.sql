-- First drop the existing foreign key if it exists
SET FOREIGN_KEY_CHECKS=0;
-- Add profile_picture column if it doesn't exist
ALTER TABLE LinkedInProfiles
ADD COLUMN IF NOT EXISTS profile_picture TEXT;

-- Recreate the foreign key with proper constraints
ALTER TABLE LinkedInProfiles
DROP FOREIGN KEY IF EXISTS LinkedInProfiles_ibfk_1;

ALTER TABLE LinkedInProfiles
ADD CONSTRAINT LinkedInProfiles_ibfk_1 
FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE;

-- Add unique constraint for user_id and linkedin_username
ALTER TABLE LinkedInProfiles
DROP INDEX IF EXISTS unique_user_profile,
ADD CONSTRAINT unique_user_profile 
UNIQUE KEY (user_id, linkedin_username);

SET FOREIGN_KEY_CHECKS=1;