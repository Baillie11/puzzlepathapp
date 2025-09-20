-- Add description column to wp2s_pp_events table
-- This allows storing custom descriptions for each hunt/quest

-- Add the description column
ALTER TABLE wp2s_pp_events 
ADD COLUMN description TEXT NULL 
AFTER location;

-- Set default "TBA" for all existing records
UPDATE wp2s_pp_events 
SET description = 'TBA' 
WHERE description IS NULL OR description = '';

-- Show the updated table structure
DESCRIBE wp2s_pp_events;

-- Show current records with the new description column
SELECT id, title, location, description, hunt_code, hunt_name 
FROM wp2s_pp_events 
ORDER BY id;
