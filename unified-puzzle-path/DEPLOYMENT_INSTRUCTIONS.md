# 🚀 Live Site Deployment Instructions

## Files to Upload

Upload these files to your live server where your Puzzle Path app is hosted:

### ✅ **Essential Files (Must Upload)**

1. **`index.html`** 
   - Location: Root directory of your Puzzle Path app
   - Changes: Now calls `verify_booking.php` instead of `test_json.php`
   - Impact: Enables real customer data from booking database

2. **`verify_booking.php`**
   - Location: Root directory of your Puzzle Path app
   - Changes: Now includes `customer_email` field and uses correct table name
   - Impact: Returns real customer names and emails from WordPress database

### 🧪 **Testing File (Recommended)**

3. **`test_database_booking.php`**
   - Location: Root directory of your Puzzle Path app
   - Purpose: Test database connection and booking verification
   - **Use this FIRST** to verify everything works before testing the main app

## 📋 **Deployment Steps**

### Step 1: Backup Your Current Files
Before uploading, backup your current:
- `index.html`
- `verify_booking.php` (if it exists)

### Step 2: Upload Files
Upload the updated files to your live site's Puzzle Path directory

### Step 3: Test Database Connection
1. Navigate to `https://yoursite.com/path-to-puzzle-path/test_database_booking.php`
2. Verify:
   - ✅ Database connects successfully
   - ✅ Booking table is found (`wp_pp_bookings` or `wp2s_pp_bookings`)
   - ✅ Customer data is visible
   - ✅ Test a real booking number

### Step 4: Test Main Application
1. Navigate to `https://yoursite.com/path-to-puzzle-path/index.html`
2. Enter a real booking number from your WordPress booking system
3. Verify you see personalized greeting (e.g., "Hello Andrew!") instead of "Hello Test Customer!"

## ⚠️ **Important Notes**

### Database Configuration
- The updated `verify_booking.php` assumes your table is named `wp2s_pp_bookings`
- If your table has a different name, you may need to adjust the table reference
- The test file will help you identify the correct table name

### WordPress Integration
- Ensure your WordPress booking plugin is active
- Verify customer data exists in the booking table
- Check that booking statuses are set correctly (paid, succeeded, confirmed, etc.)

### Fallback Plan
- If something goes wrong, restore your backup files
- The app will continue to work with your previous configuration

## 🔍 **Troubleshooting**

### If you see "Hello Test Customer!"
- The app is still using the old test endpoint
- Ensure `index.html` was uploaded correctly
- Clear browser cache

### If you get "Booking not found" errors
- Check the table name in `verify_booking.php`
- Verify booking codes match your WordPress system format
- Use the test file to debug database connectivity

### If database connection fails
- Check your `config-secure.php` database credentials
- Ensure the live server can connect to your WordPress database
- Verify database permissions

## 🎉 **Success Indicators**

You'll know it's working when:
- ✅ Real customer names appear in greetings
- ✅ "Hello [Customer Name]!" instead of "Hello Test Customer!"
- ✅ Booking verification works with real booking numbers
- ✅ No more mock/test data in the app

## 🆘 **Need Help?**

If you encounter issues:
1. Check the test file output for detailed diagnostics
2. Review browser developer console for JavaScript errors
3. Check server error logs for PHP errors
4. Verify WordPress booking plugin is functioning correctly
