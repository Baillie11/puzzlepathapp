# 🔗 WordPress Plugin + Unified App Integration Guide

This guide walks you through integrating your existing WordPress booking plugin with the new unified Puzzle Path quest application.

## 🎯 Integration Overview

### What We've Solved
1. ✅ **Booking Code Format**: Now generates hunt-specific codes (e.g., `BB-20250108-1234`)
2. ✅ **Hunt Association**: Links bookings to specific quests automatically  
3. ✅ **Database Compatibility**: Creates unified table structure
4. ✅ **Seamless User Flow**: Direct quest links after successful payment
5. ✅ **Auto-Fill Feature**: Booking numbers automatically populate in quest app

### Key Improvements
- **Enhanced Admin Interface**: Hunt code management in WordPress admin
- **Payment Integration**: Direct quest links appear after successful payment
- **Smart Booking Codes**: Format includes hunt identifier and date
- **Database Views**: Maintains compatibility with existing unified app expectations

## 📋 Step-by-Step Integration

### Phase 1: Update WordPress Plugin

1. **Backup Your Existing Plugin**
   ```bash
   # Create backup
   cp -r puzzlepath-booking-clean puzzlepath-booking-backup
   ```

2. **Replace Plugin Files**
   - Replace `puzzlepath-booking.php` with `plugin-updates/puzzlepath-booking-updated.php`
   - Replace `includes/events.php` with `plugin-updates/includes/events-updated.php`  
   - Replace `includes/stripe-integration.php` with `plugin-updates/includes/stripe-integration-updated.php`
   - Replace `js/stripe-payment.js` with `plugin-updates/js/stripe-payment-updated.js`

3. **Activate Database Updates**
   - Deactivate the plugin in WordPress admin
   - Reactivate to trigger database schema updates
   - New fields will be added automatically

### Phase 2: Configure Hunt Integration

1. **Access WordPress Admin**
   - Go to `PuzzlePath → Events`
   - You'll see new Hunt Integration fields

2. **Configure Existing Events**
   - Edit your Broadbeach event:
     - Set Hosting Type: "Self Hosted (App)"  
     - Hunt Code: "BB"
     - Hunt Name: "Broadbeach Quest"
   - Edit your Emerald Park event:
     - Set Hosting Type: "Self Hosted (App)"
     - Hunt Code: "EP" 
     - Hunt Name: "Emerald Lakes Explorer's Quest"

3. **Set Unified App URL**
   - Go to `PuzzlePath → Unified App`
   - Enter your quest app URL: `https://yoursite.com/unified-puzzle-path/`

### Phase 3: Deploy Unified Quest App

1. **Upload Unified App Files**
   - Upload the entire `unified-puzzle-path` folder to your web server
   - Make sure it's accessible via the URL you configured in step 2.3

2. **Configure Database Connection**
   - Edit `config.php` with your database credentials
   - Ensure it can access both WordPress database and user database

3. **Run Database Setup**
   - Execute `database_schema.sql` in your MySQL database
   - Execute `populate_clues.sql` to add existing quest data
   - Test with `setup_test.php` to verify everything works

### Phase 4: Test Integration

1. **Test Booking Flow**
   - Make a test booking for a hunt-enabled event
   - Verify booking code format: `BB-YYYYMMDD-XXXX`
   - Check that quest link appears after payment

2. **Test Quest Flow**  
   - Click quest link from booking confirmation
   - Verify booking number auto-fills
   - Complete a test quest to ensure all features work

## 🛠️ Configuration Details

### Booking Code Formats
```
Quest Events:    [HUNT_CODE]-[YYYYMMDD]-[XXXX]
Regular Events:  PP-[YYYYMMDD]-[XXXX]

Examples:
- BB-20250108-1234 (Broadbeach quest on Jan 8, 2025)
- EP-20250115-5678 (Emerald Park quest on Jan 15, 2025)  
- PP-20250120-9999 (Regular event on Jan 20, 2025)
```

### Database Changes
The plugin update adds these fields to `wp_pp_events`:
- `hunt_code` - 2-3 letter hunt identifier
- `hunt_name` - Full quest name

And these fields to `wp_pp_bookings`:
- `hunt_id` - Links to unified app's hunt table
- `participant_names` - Participant details
- `participant_count` - Number of people
- `booking_date` - Date of booking
- `special_requirements` - Additional notes

### WordPress Admin Features
- **Hunt Code Management**: Visual interface for associating events with quests
- **Integration Status**: See which events are linked to the unified app
- **Quick Setup Guide**: Step-by-step instructions in admin
- **Booking Code Preview**: See the format that will be generated

## 📧 Enhanced Email Confirmations

Booking confirmation emails now include:
- **Quest Information**: Hunt name and description
- **Direct Quest Link**: URL to start quest immediately  
- **Booking Code**: Clearly highlighted for easy reference
- **Instructions**: How to use the quest app

Example email addition:
```
🎯 START YOUR QUEST:
Ready to begin your Broadbeach Quest adventure?
Click here: https://yoursite.com/unified-puzzle-path/
Use your booking code: BB-20250108-1234
```

## 🚨 Troubleshooting

### Common Issues

1. **Quest Link Not Appearing**
   - Verify "Unified App URL" is set in WordPress admin
   - Check event has hunt code and is "Self Hosted (App)"
   - Ensure payment completed successfully

2. **Booking Code Wrong Format**  
   - Check event has hunt code configured
   - Verify hosting type is "Self Hosted (App)"
   - Check database fields were added correctly

3. **Database Connection Issues**
   - Verify `config.php` credentials are correct
   - Ensure both databases are accessible
   - Check database permissions

4. **Auto-Fill Not Working**
   - Verify quest link includes booking parameter
   - Check JavaScript console for errors
   - Ensure unified app URL is correct

### Verification Steps

1. **Check WordPress Integration**
   ```php
   // In WordPress admin, check if new fields exist:
   // PuzzlePath → Events → Edit Event
   // Should show Hunt Code and Hunt Name fields
   ```

2. **Check Database Schema**
   ```sql
   -- Verify new columns exist
   DESCRIBE wp_pp_events;
   DESCRIBE wp_pp_bookings;
   
   -- Check unified app tables exist
   SHOW TABLES LIKE 'wp_pp_hunts';
   SHOW TABLES LIKE 'wp_pp_clues';
   ```

3. **Test Booking Generation**
   ```php
   // Check recent booking codes
   SELECT booking_code, hunt_id FROM wp_pp_bookings 
   ORDER BY created_at DESC LIMIT 10;
   ```

## 🎉 Success Indicators

✅ **Plugin Updated Successfully**
- New hunt fields appear in Events admin
- Unified App settings page exists
- Booking codes follow new format

✅ **Integration Working**  
- Quest links appear after payment for hunt events
- Booking numbers auto-fill in quest app
- Users can complete quests and earn medals

✅ **Database Synced**
- Bookings link to correct hunts
- Clues load properly in quest app
- Analytics track completions

## 📞 Support

If you encounter issues:
1. Check the error logs (`error.log` in unified app directory)
2. Test with the included `setup_test.php` file
3. Verify database permissions and connections
4. Ensure all files were uploaded correctly

The integration maintains full backward compatibility - existing bookings and functionality remain unchanged while adding the new quest features!
