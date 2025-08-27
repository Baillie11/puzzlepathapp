# Puzzle Path Unified App - Live Deployment Checklist

## Pre-Deployment Preparation

### 1. Run the Deployment Script
- Execute `deploy-to-live.bat` to copy production files to the `deploy` folder
- This creates a clean package with only the necessary files

### 2. Update Configuration for Live Environment
- Copy `config-live.php` to `config.php` in your deploy folder
- Update the following placeholders in `config.php`:

```php
// Database credentials
'host' => 'YOUR_LIVE_DB_HOST',           // e.g., 'localhost' 
'username' => 'YOUR_LIVE_DB_USERNAME',   // Your database username
'password' => 'YOUR_LIVE_DB_PASSWORD',   // Your database password
'database' => 'YOUR_LIVE_DB_NAME',       // Your database name

// Domain settings
'allowed_origins' => [
    'https://yourdomain.com',            // Your actual domain
    'https://www.yourdomain.com'         // Include www if needed
],

// Admin email for error notifications
'admin_email' => 'admin@yourdomain.com'  // Your email address

// Log file path (make sure directory exists and is writable)
'log_file' => '/path/to/logs/puzzle-path-errors.log'
```

## Files to Upload to Live Server

Upload these files from the `deploy` folder to your live server:

### Core Application Files
- ✅ `index.html` - Main quest interface
- ✅ `register.html` - Registration page after quest completion  
- ✅ `config.php` - Database configuration (updated with live credentials)

### Backend API Files
- ✅ `verify_booking.php` - Booking verification endpoint
- ✅ `get_clues.php` - Clue retrieval endpoint
- ✅ `track_quest.php` - Quest progress tracking

### Assets
- ✅ `puzzlepath-logo-web.png` - Logo image
- ✅ `Broadbeach Medal.png` - Achievement badge

### Database Setup Files
- ✅ `database_schema.sql` - Run this on your live database first
- ✅ `populate_clues.sql` - Run this after schema to add sample clues

## Live Server Setup Steps

### 3. Database Setup
1. **Create the database tables:**
   - Log into your live database (phpMyAdmin, MySQL Workbench, or command line)
   - Run the SQL commands from `database_schema.sql`
   - This creates: `hunts`, `clues`, `quest_sessions` tables and `wp2s_pp_bookings` view

2. **Add sample clues:**
   - Run the SQL commands from `populate_clues.sql`
   - This adds sample hunts and clues for testing

### 4. WordPress Plugin Integration
1. **Update your WordPress booking plugin** with the integration changes
2. **Set the unified app URL** in WordPress Admin:
   - Go to your WordPress admin panel
   - Navigate to the Puzzle Path booking plugin settings
   - Set "Unified App URL" to: `https://yourdomain.com/path-to-app/`

### 5. Server Configuration

#### File Permissions
Ensure these permissions on your server:
- PHP files: `644` (readable/writable by owner, readable by group/others)
- Images: `644` 
- Log directory: `755` (if using custom log path)

#### PHP Requirements
- PHP 7.4+ (recommended 8.0+)
- PDO MySQL extension enabled
- JSON extension enabled
- Session support enabled

#### Security Headers (Optional but Recommended)
Add these to your `.htaccess` file:

```apache
# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

# Hide PHP version
Header unset X-Powered-By
ServerTokens Prod
```

## Testing Your Live Deployment

### 6. Verify Everything Works
1. **Test the main page:** `https://yourdomain.com/path-to-app/`
2. **Test booking verification:** Use a WordPress booking code
3. **Test quest flow:** Complete at least one clue
4. **Check error logs:** Monitor for any PHP errors

### 7. WordPress Integration Test
1. **Make a test booking** in WordPress
2. **Note the booking code** generated
3. **Use that code** in the unified app
4. **Verify it works** end-to-end

## Post-Deployment Monitoring

### Error Monitoring
- Check your error log regularly: `/path/to/logs/puzzle-path-errors.log`
- Monitor server error logs for PHP issues
- Set up email notifications for critical errors (if configured)

### Performance Monitoring
- Monitor database query performance
- Check page load times
- Monitor API response times

## Troubleshooting Common Issues

### Database Connection Issues
- Verify database credentials in `config.php`
- Ensure database server allows connections from web server
- Check database user permissions

### CORS Issues
- Verify `allowed_origins` in `config.php` matches your domain exactly
- Include both `https://yourdomain.com` and `https://www.yourdomain.com`

### WordPress Integration Issues
- Verify WordPress plugin is updated with integration changes
- Check that unified app URL is set correctly in WordPress settings
- Ensure `wp2s_pp_bookings` view exists in database

### File Permission Issues
- Ensure web server can read all PHP files
- Ensure log directory is writable (if using custom logging)
- Check that images are accessible via HTTP

## Security Recommendations

1. **HTTPS Only:** Ensure your site uses HTTPS (SSL certificate)
2. **Database Security:** Use strong database passwords, limit user permissions
3. **File Security:** Keep sensitive files outside web root when possible
4. **Regular Updates:** Keep PHP and server software updated
5. **Backup Strategy:** Implement regular database and file backups

---

## Quick Deployment Commands

```bash
# Upload files (example using SCP)
scp deploy/* user@yourserver.com:/path/to/web/directory/

# Or using FTP/SFTP client, upload all files from deploy/ folder

# Database setup (example using MySQL command line)
mysql -u username -p database_name < database_schema.sql
mysql -u username -p database_name < populate_clues.sql
```

---

**🎉 Once deployed, your unified Puzzle Path app will be live and ready to accept bookings from your WordPress site!**
