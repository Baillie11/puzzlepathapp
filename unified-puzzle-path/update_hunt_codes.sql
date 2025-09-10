-- SQL script to update hunt codes and titles in wp2s_pp_events table
-- This ensures the hunt codes match the mapping function and titles are correct

-- Update Broadbeach Adventure (Hunt ID 9)
UPDATE wp2s_pp_events 
SET hunt_code = 'BBR1', title = 'Broadbeach Adventure', hunt_name = 'broadbeach_adventure'
WHERE id = 9;

-- Update Emerald Lakes (Hunt ID 10)
UPDATE wp2s_pp_events 
SET hunt_code = 'EL', title = 'Emerald Lakes Explorer', hunt_name = 'emerald_lakes'
WHERE id = 10;

-- Update Koala Trail (Hunt ID 11)
UPDATE wp2s_pp_events 
SET hunt_code = 'KOALA', title = 'Gold Coast Koala Trail', hunt_name = 'koala_trail'
WHERE id = 11;

-- Update Surfers Paradise (Hunt ID 12)
UPDATE wp2s_pp_events 
SET hunt_code = 'SP', title = 'Surfers Paradise Explorer', hunt_name = 'surfers_paradise'
WHERE id = 12;

-- Update Springbrook (Hunt ID 15)
UPDATE wp2s_pp_events 
SET hunt_code = 'SPRINGBROOK', title = 'Springbrook Adventure', hunt_name = 'springbrook'
WHERE id = 15;

-- Update Coolangatta Heritage (Hunt ID 16)
UPDATE wp2s_pp_events 
SET hunt_code = 'COOLANGATTA', title = 'Coolangatta Heritage Walk', hunt_name = 'coolangatta_heritage'
WHERE id = 16;

-- Update Date Night (Hunt ID 17)
UPDATE wp2s_pp_events 
SET hunt_code = 'DATE_NIGHT', title = 'Romantic Date Night Quest', hunt_name = 'date_night'
WHERE id = 17;

-- Update Sandbox Test (Hunt ID 18)
UPDATE wp2s_pp_events 
SET hunt_code = 'SANDBOX', title = 'Sandbox Test Quest', hunt_name = 'sandbox_test'
WHERE id = 18;

-- Update Tamborine Mountain (Hunt ID 19)
UPDATE wp2s_pp_events 
SET hunt_code = 'TAMBORINE', title = 'Tamborine Mountain Explorer', hunt_name = 'tamborine_mountain'
WHERE id = 19;

-- Update Currumbin Rockpools (Hunt ID 20)
UPDATE wp2s_pp_events 
SET hunt_code = 'CURRUMBIN', title = 'Currumbin Rockpools Adventure', hunt_name = 'currumbin_rockpools'
WHERE id = 20;

-- Update Southport Rockpools (Hunt ID 21)
UPDATE wp2s_pp_events 
SET hunt_code = 'SOUTHPORT', title = 'Southport Rockpools Explorer', hunt_name = 'southport_rockpools'
WHERE id = 21;

-- Verify the updates
SELECT id, hunt_code, title, hunt_name FROM wp2s_pp_events WHERE id IN (9,10,11,12,15,16,17,18,19,20,21) ORDER BY id;
