# 🚀 Live Site Deployment Checklist

## ✅ **Essential Files to Upload**

### **Core Application Files**
Upload these files to your live site root directory:

#### **1. Configuration (CRITICAL)**
```
config-secure.php          # Updated dynamic hunt system
```
⚠️ **Important**: Make sure to update database credentials for live site in this file!

#### **2. Main Application Files**
```
index.html                 # Updated quest interface with session persistence
verify_booking.php         # Updated booking verification with new hunt mapping
get_clues.php             # Clue loading system
mark_booking_used.php     # Booking redemption system
track_quest.php           # Quest progress tracking
```

#### **3. Asset Directories**
```
uploads/                  # Create empty directory for photo uploads
medals/                   # Medal images (if you have any)
```

#### **4. Images (if needed)**
```
puzzlepath-logo-web.png   # App logo
Broadbeach Medal.png      # Medal image (optional)
```

## 🗄️ **Database Updates Required**

Run this SQL script on your live database:

```sql
-- From update_hunt_codes.sql file
UPDATE wp2s_pp_events SET hunt_code = 'BBR1', title = 'Broadbeach Adventure', hunt_name = 'broadbeach_adventure' WHERE id = 9;
UPDATE wp2s_pp_events SET hunt_code = 'EL', title = 'Emerald Lakes Explorer', hunt_name = 'emerald_lakes' WHERE id = 10;
UPDATE wp2s_pp_events SET hunt_code = 'KOALA', title = 'Gold Coast Koala Trail', hunt_name = 'koala_trail' WHERE id = 11;
UPDATE wp2s_pp_events SET hunt_code = 'SP', title = 'Surfers Paradise Explorer', hunt_name = 'surfers_paradise' WHERE id = 12;
UPDATE wp2s_pp_events SET hunt_code = 'SPRINGBROOK', title = 'Springbrook Adventure', hunt_name = 'springbrook' WHERE id = 15;
UPDATE wp2s_pp_events SET hunt_code = 'COOLANGATTA', title = 'Coolangatta Heritage Walk', hunt_name = 'coolangatta_heritage' WHERE id = 16;
UPDATE wp2s_pp_events SET hunt_code = 'DATE_NIGHT', title = 'Romantic Date Night Quest', hunt_name = 'date_night' WHERE id = 17;
UPDATE wp2s_pp_events SET hunt_code = 'SANDBOX', title = 'Sandbox Test Quest', hunt_name = 'sandbox_test' WHERE id = 18;
UPDATE wp2s_pp_events SET hunt_code = 'TAMBORINE', title = 'Tamborine Mountain Explorer', hunt_name = 'tamborine_mountain' WHERE id = 19;
UPDATE wp2s_pp_events SET hunt_code = 'CURRUMBIN', title = 'Currumbin Rockpools Adventure', hunt_name = 'currumbin_rockpools' WHERE id = 20;
UPDATE wp2s_pp_events SET hunt_code = 'SOUTHPORT', title = 'Southport Rockpools Explorer', hunt_name = 'southport_rockpools' WHERE id = 21;
```

## ⚙️ **Live Site Configuration**

### **1. Update Database Credentials**
Edit `config-secure.php` and update these lines for your live database:

```php
define('DB_HOST', 'your_live_db_host');
define('DB_NAME', 'your_live_db_name'); 
define('DB_USER', 'your_live_db_user');
define('DB_PASS', 'your_live_db_password');
```

### **2. Set Permissions**
```bash
chmod 755 uploads/
chmod 755 medals/
chmod 644 *.php
chmod 644 *.html
```

### **3. Test Live Site**
After upload, test with a booking code like:
- `CLG562-20240901-1234` (should show Coolangatta quest)
- `BBR1-20240901-5678` (should show Broadbeach quest)

## 🔄 **What's New in This Update**

✅ **Dynamic hunt recognition** - New hunts auto-detected  
✅ **Fixed quest name display** - Shows correct hunt titles  
✅ **Fixed clue loading** - Clues now load for all hunts  
✅ **Fixed booking validation** - Now accepts longer hunt codes (CLG562, SPRINGBROOK, etc.)  
✅ **Session persistence** - Progress saved on page refresh  
✅ **Booking redemption** - Prevents reuse of booking codes  
✅ **Enhanced error handling** - Better debugging and security

## 🚫 **Files NOT to Upload**

Don't upload these test/development files:
```
test_booking_codes.php
debug_hunt_mapping.php
update_hunt_codes.sql
HOW_TO_ADD_NEW_HUNTS.md
config.php (old version)
config-test.php
config-local.php
*-UPDATED.php
*-FINAL.php
check_*.php
setup_*.php
```

## 🔍 **Post-Deployment Testing**

1. **Access your live site**
2. **Enter a booking code** (e.g., `CLG562-20240901-1234`)
3. **Verify correct quest loads** (should show Coolangatta, not Broadbeach)
4. **Check clues load properly** 
5. **Test photo/answer submission**
6. **Confirm quest completion works**

## 🆘 **Troubleshooting**

If something doesn't work:

1. **Check file permissions** (755 for directories, 644 for files)
2. **Verify database credentials** in config-secure.php
3. **Check database has been updated** with new hunt codes
4. **Review error logs** in your hosting control panel
5. **Test booking codes** match your database hunt_code values

## 🎯 **Key Benefits of This Update**

- **Scalable**: Add new hunts without code changes
- **Reliable**: Fixed hunt mapping and clue loading
- **User-friendly**: Proper quest names and descriptions
- **Robust**: Better error handling and session management

Your live site should now properly recognize all hunt types and display the correct quest information! 🎉
