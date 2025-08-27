# 🚀 REST API Integration Update

## Overview

The unified app has been updated to use the new REST API endpoints from the WordPress plugin instead of direct database access. This provides better security, maintainability, and compatibility.

## What Changed

### 1. **WordPress Plugin** (Previously Updated)
- Added REST API endpoints:
  - `GET /wp-json/puzzlepath/v1/bookings` - Get filtered bookings
  - `GET /wp-json/puzzlepath/v1/booking/{code}` - Get specific booking by code
  - `GET /wp-json/puzzlepath/v1/hunts` - Get available hunts

### 2. **Unified App Updates** (Just Completed)

#### Updated Files:
- **`verify_booking.php`** - Now uses WordPress REST API instead of direct database queries
- **`config.php`** - Added WordPress API URL configuration

#### Key Changes:
- **Security**: No more direct database access to WordPress tables
- **Compatibility**: Works with WordPress plugin's payment status (`succeeded` vs `paid`)
- **Resilience**: Better error handling and API timeouts
- **Maintainability**: Cleaner separation between WordPress and app logic

## Configuration Required

### WordPress API URL
Update the `WORDPRESS_API_BASE_URL` in `config.php`:

```php
// For production
define('WORDPRESS_API_BASE_URL', 'https://puzzlepath.com.au/wp-json/puzzlepath/v1');

// For testing  
define('WORDPRESS_API_BASE_URL', 'http://localhost/your-site/wp-json/puzzlepath/v1');
```

### Payment Status Compatibility
The app now handles both status formats:
- **WordPress Plugin**: `succeeded`/`pending`/`failed`/`refunded`
- **Unified App**: Maps to `confirmed`/`pending`/`cancelled`

## API Flow

### Before (Direct Database)
```
Unified App → WordPress Database → Booking Data
```

### After (REST API)
```
Unified App → WordPress REST API → WordPress Plugin → Unified Bookings View → Response
```

## Benefits

1. **🔒 Security**: No database credentials exposed to unified app
2. **🛠️ Maintainability**: Changes to WordPress plugin automatically available to app
3. **📊 Consistent Data**: Uses the same unified bookings view as WordPress admin
4. **🚀 Performance**: WordPress handles caching and optimization
5. **🔄 Future-Proof**: Easy to add authentication, rate limiting, etc.

## Error Handling

The updated system handles:
- **Network failures** - Connection timeouts and retries
- **API errors** - Invalid booking codes, payment status issues
- **Data validation** - Ensures required fields are present
- **Graceful degradation** - User-friendly error messages

## Testing

Test the integration:

1. **Valid Booking**: Use a confirmed booking code (e.g., `BB-20250116-1234`)
2. **Invalid Booking**: Try non-existent code
3. **Unpaid Booking**: Use pending/failed payment booking
4. **Network Issues**: Test with wrong API URL

## Deployment Steps

1. **Upload WordPress Plugin Updates** (if not already done)
2. **Upload Updated Unified App Files**:
   - `verify_booking.php`
   - `config.php`
3. **Update Configuration**:
   - Set correct `WORDPRESS_API_BASE_URL`
4. **Test Integration**:
   - Verify booking numbers work
   - Check error handling
   - Confirm quest flow continues normally

## API Endpoints Available

The WordPress plugin now provides these endpoints for future use:

### Get All Bookings (Filtered)
```bash
GET /wp-json/puzzlepath/v1/bookings?hunt_id=BB&status=confirmed
```

### Get Specific Booking  
```bash
GET /wp-json/puzzlepath/v1/booking/BB-20250116-1234
```

### Get Available Hunts
```bash
GET /wp-json/puzzlepath/v1/hunts?active_only=true
```

## Future Enhancements

With this REST API foundation, you can now easily add:
- **Authentication** for secure API access
- **Rate limiting** to prevent abuse
- **Caching** for better performance
- **Webhooks** for real-time updates
- **Mobile app integration** using the same endpoints
- **Analytics** and reporting features

## Troubleshooting

### Common Issues:

1. **"Unable to connect to booking system"**
   - Check `WORDPRESS_API_BASE_URL` in `config.php`
   - Verify WordPress site is accessible
   - Check network connectivity

2. **"Booking number not found"**
   - Verify booking exists in WordPress
   - Check booking code format
   - Ensure WordPress plugin is activated

3. **"Payment not confirmed"**
   - Booking exists but payment status is not `succeeded`
   - Check payment processing in WordPress admin

### Debug Mode:
Enable error logging in `verify_booking.php` for detailed debugging:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1); // Only for testing!
```

The integration maintains full backward compatibility while providing a much more robust and secure connection between your WordPress booking system and unified quest app!
