-- Populate clues for existing hunts
-- This script adds all the clues from Broadbeach and Emerald Park hunts

-- Broadbeach Quest Clues (hunt_id = 1)
INSERT INTO pp_clues (hunt_id, clue_order, title, clue_text, task_description, hint_text, answer_key) VALUES
(1, 1, 'Clue 1: Sound & Sand', 
 'Where music meets the sand and grass,\nA stage stands proud where events do pass.\nFace the sea, your journey\'s begun —\nFind the shell that captures sound and sun.',
 'Form a \'band\' using air instruments and take a group photo on the amphitheatre stage.',
 'It\'s shaped like a shell and often hosts music or markets.',
 'Kurrawa Park Amphitheatre'),

(1, 2, 'Clue 2: A Golden Stroll',
 'Walk the path where runners glide,\nBeside the beach, the ocean\'s tide.\nFind the exercise zone where you can flex —\nIt\'s part of fitness, not just for pecs.',
 'Pick one group member to demonstrate a piece of equipment while others cheer like it\'s the Olympics.',
 'It\'s free, public, and often used by beachgoers.',
 'Outdoor gym equipment area near Old Burleigh Rd'),

(1, 3, 'Clue 3: Tall and Timely',
 'A tower of time with a golden hue,\nIt marks the spot with a coastal view.\nCount its stories from base to sky,\nThen turn west and wave goodbye.',
 'Snap a group photo with the clock in the background, mid \'freeze-frame\' like you\'re caught in time.',
 'It\'s a tribute to timekeeping, not a hotel.',
 'Broadbeach Clock Tower'),

(1, 4, 'Clue 4: Literary Escape',
 'Not far from sand, in shade and peace,\nA place for books where whispers cease.\nChairs, not waves, are where you\'ll dwell,\nFind the tales you know so well.',
 'Each team member must find a book that starts with the same letter as their first name and pose like a character from the cover.',
 'It\'s quiet, cool, and full of shelves.',
 'Broadbeach Library'),

(1, 5, 'Clue 5: Beneath the Arches',
 'Where arches glow as sunsets fall,\nThe foreshore welcomes one and all.\nReturn to waves and sandy floor,\nA photo near these lights is lore.',
 'Take a creative group beach photo using the arch as a frame — bonus if everyone is jumping mid-air.',
 'The lights come on at dusk and shine bright at night.',
 'Kurrawa Arches'),

(1, 6, 'Clue 6: Memory Lane',
 'Final stop, near where you began,\nA playground built for every clan.\nLook for signs with plaques and dates,\nTime to tally and test your fates.',
 'Find three plaques or signs with dates. Use them in a math equation (e.g., oldest year minus youngest participant\'s age) and submit your answer to unlock the medal.',
 'You passed it at the start. It\'s where fun and inclusivity meet.',
 'Pratten Park All Abilities Playground');

-- Emerald Park Quest Clues (hunt_id = 2)  
INSERT INTO pp_clues (hunt_id, clue_order, title, clue_text, task_description, hint_text, answer_key) VALUES
(2, 1, 'Clue 1: Counting Light',
 'This bridge guides both day and night,\nWith metal posts that give off light.\nWalk from end to end and count each pole —\nNot bollards, just the tall light goal.',
 'Count the tall light poles along the bridge.',
 'Only count the tall upright light poles — not small ones or ground fixtures.',
 '8'),

(2, 2, 'Clue 2: Steel Strength',
 'Along the lake, where runners train,\nStands gear to stretch your back or strain.\nOne has bars in a laddered row —\nWhat colour are the handles where your hands would go?',
 'Visit the exercise station and check the ladder-like bar handles.',
 'Look near the water\'s edge, past the bridge. It\'s shaped like a ladder.',
 'Silver'),

(2, 3, 'Clue 3: Carved in Stone',
 'Just off the track, a plaque is found,\nIn stone it rests near the picnic ground.\nWhat year is carved to mark the day\nThis place was opened in a formal way?',
 'Find the stone plaque near the BBQ area. What year is engraved?',
 'Look low to the ground, near picnic tables or paths.',
 '2003'),

(2, 4, 'Clue 4: The Sculpture Watcher',
 'A figure waits, not made of flesh,\nBut metal curves or angles fresh.\nFind the sculpture near the bend —\nWhat is the first word of the plaque at its end?',
 'Read the sculpture\'s plaque. What\'s the first word?',
 'The sculpture is near the first major bend in the lake trail.',
 'Commissioned'),

(2, 5, 'Clue 5: Colour by Nature',
 'Look to the gardens with planned design,\nThree flower beds in a tidy line.\nOne red, one white, one violet too —\nWhich flower is planted with the colour blue?',
 'Identify the blue-flowering plant. What is it?',
 'Check landscaped flower beds near the lake\'s western side.',
 'Agapanthus'),

(2, 6, 'Clue 6: Benched With a View',
 'Along the lake, one bench stands proud,\nFacing the water, away from the crowd.\nIt\'s not curved or in a set —\nJust one alone — have you found it yet?',
 'Find the lone bench with a lake view. Take a selfie there.',
 'Look along the southern lake path for a solo bench facing open water.',
 'Photo confirmation'),

(2, 7, 'Clue 7: Final Fill-Up',
 'Before you finish, hydrate true,\nThere\'s one blue fountain waiting for you.\nWhat small raised icon is on the tap —\nNot letters — just a watery map?',
 'Describe the symbol (not text) on the fountain.',
 'Look near shaded areas or fitness spots for the water station.',
 'Water droplet'),

(2, 8, 'Clue 8: Hero\'s Seat',
 'With all clues solved, you\'ve reached the end,\nYour journey\'s done, adventurer friend.\nOne wide bench gives a champion\'s view —\nTake your photo, the quest is through!',
 'Find the largest bench near the starting playground with lake view.',
 'You saw this bench at the start near the All Abilities Playground.',
 'Final victory photo');
