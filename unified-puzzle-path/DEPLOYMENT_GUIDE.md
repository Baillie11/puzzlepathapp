# 🚀 Puzzle Path - Complete Deployment Checklist

## ✅ **Essential Files to Upload**

### **Core Application Files** (REQUIRED)
```
✅ config.php                    ← Database configuration (FIXED)
✅ verify_booking.php             ← Booking verification (FIXED)
✅ get_clues.php                  ← Clue loading system (FIXED)
✅ track_quest.php                ← Quest progress tracking
✅ index.html                     ← Main quest interface (if you have it)
```

### **Setup & Testing Files** (HELPFUL)
```
✅ setup_clues_database.php       ← Creates clues table & adds test data
✅ check_database_setup.php       ← Verifies database connections
✅ check_table_structure.php      ← Shows actual table structures
✅ test_booking_simple.php        ← Tests booking verification
```

### **Optional Diagnostic Files** (FOR TROUBLESHOOTING)
```
⚪ check_clue_setup.php          ← Analyzes clue system
⚪ fix_booking_connection.php    ← Connection diagnostics (not needed now)
⚪ test_api_connection.php       ← API testing (not needed now)
```

## 📋 Files to Upload to Live Site

### ✅ **Essential PHP Files** (FIXED for single database)
```
verify_booking.php          ← Main booking verification (FIXED)
config.php                  ← Database configuration (FIXED)
get_clues.php              ← Clue loading system
track_quest.php            ← Quest progress tracking
index.html                 ← Main app interface (if you have it)
```

### ✅ **Testing Files** (Helpful for debugging)
```
check_database_setup.php   ← Check if database is set up correctly (UPDATED)
test_booking_simple.php    ← Test booking verification
```

### ❌ **Files NOT Needed** (Single database approach)
```
database_schema.sql        ← NOT NEEDED (using existing WordPress tables)
populate_clues.sql         ← NOT NEEDED (using existing WordPress tables)
```

---

## 🔧 **Step-by-Step Deployment**

### **Step 1: Upload Files**
Upload these files to your live site directory:
- `verify_booking.php` (the FIXED version)
- `config.php` (the FIXED version)
- `get_clues.php` 
- `track_quest.php`
- `check_database_setup.php` (for testing)
- `test_booking_simple.php` (for testing)

### **Step 2: Ensure WordPress Tables Exist**
Your WordPress database (`ozbizfin_wp793`) should already have these tables:
- ✅ `wp2s_pp_bookings` (booking data)
- ✅ `wp2s_pp_events` (hunt/event data) 
- ✅ `wp2s_pp_coupons` (coupon data)

**If any tables are missing**, they should be created by your WordPress plugin.

### **Step 3: Add Hunt Data to wp2s_pp_events**
You need to add your hunt data to the `wp2s_pp_events` table. Example:

```sql
INSERT INTO wp2s_pp_events (event_code, event_title, event_location, event_description, event_short_description, event_active) VALUES
('BB', 'Broadbeach Quest', 'Broadbeach', 'Explore the vibrant Broadbeach area with this exciting scavenger hunt!', 'Start at the All Abilities Playground and follow the path clockwise.', 1),
('EP', 'Emerald Lakes Explorer Quest', 'Emerald Park', 'Discover the beauty of Emerald Lakes in this nature-focused adventure!', 'Start at the All Abilities Playground and follow the path clockwise.', 1);
```

### **Step 4: Test the System**
1. Visit: `your-site.com/check_database_setup.php`
2. Verify database connection and all 3 tables exist
3. Visit: `your-site.com/test_booking_simple.php` 
4. Test with a real booking number

---

## 📊 **Database Structure (Single WordPress Database)**

### **WordPress Database** (`ozbizfin_wp793`) - Contains EVERYTHING:
- `wp2s_pp_bookings` ← Booking and payment data
- `wp2s_pp_events` ← Hunt/event definitions (BB, EP hunts)
- `wp2s_pp_coupons` ← Coupon system

**No separate databases needed!** Everything is in your WordPress database.

---

## ✅ **Issue FIXED**

The error `Table 'ozbizfin_puzzlepath.pp_hunts' doesn't exist` is now FIXED because:

1. ✅ App now uses single WordPress database (`ozbizfin_wp793`)
2. ✅ App looks for hunt data in `wp2s_pp_events` table (not `pp_hunts`)
3. ✅ No more separate database connections needed

---

## 🧪 **Testing Checklist**

After deployment, verify:

- [ ] `check_database_setup.php` shows all databases connected
- [ ] Hunt table shows: BB (Broadbeach) and EP (Emerald Park) hunts
- [ ] `test_booking_simple.php` successfully verifies a booking
- [ ] Main app can load clues and track progress
- [ ] No "Table doesn't exist" errors

---

## 🔍 **Troubleshooting**

### **"Table doesn't exist" Error**
- Run `database_schema.sql` in phpMyAdmin on `ozbizfin_puzzlepath` database

### **"Database connection failed" Error**
- Check database credentials in `config.php`
- Verify database names match your hosting setup

### **"Booking not found" Error**  
- Verify `wp2s_pp_bookings` table exists in `ozbizfin_wp793`
- Check booking exists with correct payment status

---

## 📱 **Your App Architecture** 

```
Booking Verification:
WordPress DB (ozbizfin_wp793) → wp2s_pp_bookings → Check payment status

Hunt Data Loading:
Puzzle Path DB (ozbizfin_puzzlepath) → pp_hunts → Get hunt info
                                    → pp_clues → Load clues
```

This two-database setup gives you the best of both worlds:
- WordPress handles bookings/payments
- Standalone app handles hunt logic and user experience
