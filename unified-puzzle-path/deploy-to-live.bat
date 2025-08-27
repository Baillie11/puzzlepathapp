@echo off
echo ================================
echo  Puzzle Path Unified App Deployer
echo ================================
echo.

REM Create deployment directory
if not exist "deploy" mkdir deploy
echo Created deploy directory...

REM Copy essential production files
echo Copying production files...

REM Core application files
copy "index.html" "deploy\" >nul
copy "register.html" "deploy\" >nul
copy "config.php" "deploy\" >nul

REM Backend API files
copy "verify_booking.php" "deploy\" >nul
copy "get_clues.php" "deploy\" >nul
copy "track_quest.php" "deploy\" >nul

REM Assets
copy "puzzlepath-logo-web.png" "deploy\" >nul
copy "Broadbeach Medal.png" "deploy\" >nul

REM Database setup files
copy "database_schema.sql" "deploy\" >nul
copy "populate_clues.sql" "deploy\" >nul

echo.
echo ✅ Production files copied to 'deploy' folder:
echo    - index.html (main quest interface)
echo    - register.html (registration page)
echo    - config.php (database configuration)
echo    - verify_booking.php (booking API)
echo    - get_clues.php (clues API)
echo    - track_quest.php (progress tracking)
echo    - puzzlepath-logo-web.png (logo)
echo    - Broadbeach Medal.png (achievement badge)
echo    - database_schema.sql (DB structure)
echo    - populate_clues.sql (sample data)
echo.

echo 📋 NEXT STEPS FOR LIVE DEPLOYMENT:
echo.
echo 1. Upload all files from 'deploy' folder to your live server
echo 2. Update config.php with your live database credentials
echo 3. Run database_schema.sql on your live database
echo 4. Run populate_clues.sql to add sample clues
echo 5. Update your WordPress plugin with the unified app URL
echo.

echo ⚠️  IMPORTANT: Don't forget to update config.php with live DB settings!
echo.

pause
