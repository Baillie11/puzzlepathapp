# Puzzle Path App - Changelog

## Version 3.0.0 - Dynamic Hunt System (2025-01-10)

### 🚀 Major Features
- **Dynamic Hunt Recognition**: Automatically detects new hunts when added to database - no code changes needed!
- **Database-Driven Mapping**: Hunt codes are now mapped dynamically from the database instead of hardcoded
- **Scalable Architecture**: Add unlimited new hunts by simply updating the database

### ✅ Bug Fixes
- **Fixed Quest Name Display**: Shows correct hunt titles instead of always showing "Broadbeach Adventurer"
- **Fixed Clue Loading**: Real clues now load properly from database instead of test clues
- **Fixed Booking Validation**: Now accepts longer hunt codes (CLG562, SPRINGBROOK, etc.)
- **Fixed Hunt Mapping**: Booking codes like CLG562-YYYYMMDD-XXXX now correctly map to Coolangatta quest

### 🔧 Technical Improvements
- **Enhanced Session Persistence**: Quest progress is now saved and restored on page refresh
- **Booking Redemption System**: Prevents booking codes from being reused after quest completion
- **Improved Error Handling**: Better security, logging, and user-friendly error messages
- **Live Database Integration**: Updated to work with live site database credentials

### 🏗️ Infrastructure
- **Security Enhancements**: Enhanced input validation, CSRF protection, rate limiting
- **Database Optimization**: More efficient queries and better connection handling
- **Code Organization**: Cleaner separation of concerns and better maintainability

### 📋 Hunt Support
This version supports all hunt types including:
- Broadbeach Adventure (BBR1)
- Emerald Lakes Explorer (EL)
- Gold Coast Koala Trail (KOALA)
- Surfers Paradise Explorer (SP)
- Coolangatta Heritage Walk (COOLANGATTA)
- Springbrook Adventure (SPRINGBROOK)
- Date Night Quest (DATE_NIGHT)
- Tamborine Mountain Explorer (TAMBORINE)
- Currumbin Rockpools (CURRUMBIN)
- Southport Rockpools (SOUTHPORT)
- Sandbox Test Quest (SANDBOX)

### 🎯 Breaking Changes
- Hunt mapping is now database-driven - ensure your wp2s_pp_events table has correct hunt_code values
- Booking validation now requires proper format: HUNTCODE-YYYYMMDD-XXXX

### 📦 Files Changed
- `config-secure.php` - Dynamic hunt system, updated credentials, enhanced security
- `verify_booking.php` - Fixed booking validation and hunt mapping
- `get_clues.php` - Database-driven clue loading instead of test clues
- `index.html` - Session persistence and UI improvements
- `mark_booking_used.php` - New booking redemption system

---

## Version 2.1.0 - Previous Release
- Basic hunt system with hardcoded mappings
- Test clue system
- Simple booking verification

---

## Migration Guide from 2.x to 3.0.0

1. **Update Database**: Run the SQL updates to ensure hunt_code fields are properly set
2. **Update Config**: Use new config-secure.php with live database credentials
3. **Test Hunt Codes**: Verify all booking codes map to correct hunts
4. **Deploy Files**: Upload all updated files to live site

For detailed deployment instructions, see `LIVE_SITE_DEPLOYMENT.md`
