# Email Template Fix - Deployment Instructions

## Problem Fixed
- **White text on white background** in booking confirmation emails
- Missing HTML email template causing visibility issues

## Files to Update

### 1. Upload `plugin-updates/includes/settings.php` to:
**WordPress Plugin Directory:** `wp-content/plugins/puzzlepath-booking/includes/settings.php`

**Important:** This is a **NEW FILE** that contains the HTML email template function `get_email_template()`. Make sure the `includes/` directory exists in your plugin folder.

### 2. Replace existing file: `plugin-updates/includes/stripe-integration-updated.php`
**WordPress Plugin Directory:** `wp-content/plugins/puzzlepath-booking/includes/stripe-integration.php`

**Note:** This file has been updated with:
- Modified `send_confirmation_email()` function to use HTML templates
- New `process_free_booking()` endpoint for 100% discounted bookings
- Proper email headers for HTML content

## Key Changes Made

### HTML Email Template (`settings.php`)
- **Dark text on light backgrounds** - fixes visibility issues
- **Responsive design** that works across email clients
- **Professional styling** with PuzzlePath branding
- **Quest link integration** with prominent call-to-action buttons
- **Booking code highlight** with clear visibility

### Email Function Updates (`stripe-integration.php`)
- Uses new HTML template when available
- Sets proper `Content-Type: text/html` headers
- Includes branded "From" header
- Maintains backward compatibility with plain text fallback

### New Free Booking Endpoint
- Handles 100% discounted bookings without Stripe
- Sends same HTML confirmation email
- Fully processes booking (database, seat count, coupon usage)

## Testing Steps

1. **Make a test booking** with 100% discount coupon
2. **Check email receipt** - should now have:
   - Dark text on light backgrounds (no more white on white)
   - Professional HTML formatting
   - Visible booking code
   - Working quest link button (if applicable)
3. **Test on different email clients** (Gmail, Outlook, etc.)

## Backup Instructions

Before uploading, backup your current plugin files:
- `wp-content/plugins/puzzlepath-booking/includes/stripe-integration.php`

## Email Template Features

The new HTML email template includes:
- ✅ **Fully visible text** (dark on light backgrounds)
- ✅ **Mobile responsive design**
- ✅ **PuzzlePath branding and logo**
- ✅ **Clear booking details table**
- ✅ **Prominent booking code display**
- ✅ **Quest launch button** (for self-hosted hunts)
- ✅ **Professional footer with contact info**

## Result

Booking confirmation emails will now display properly with:
- **No more white text on white background**
- **Professional, branded appearance**
- **Clear visibility of all booking information**
- **Enhanced user experience**

The email will look similar to the original but with proper contrast and styling that works across all email clients.