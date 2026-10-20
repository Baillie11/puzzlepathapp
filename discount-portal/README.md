# Puzzle Path Discount Portal

A mobile-first private portal backed by the existing PuzzlePath Booking `pp_coupons` table. It creates percentage codes that the booking plugin reads and associates with bookings through `coupon_id`. No WordPress dashboard login is required for daily use.

## Installation

1. Upload this folder as `wp-content/plugins/discount-portal/` on the **same WordPress installation as PuzzlePath Booking**. Activate “Puzzle Path Discount Portal” once through Plugins (or use WP-CLI).
2. Open **Settings > Puzzle Path Portal**. Enter and confirm a unique password of at least 12 characters, then click **Save portal password**. Only its hash is stored. The password must be entered by the owner.
3. Alternatively, administrators managing configuration in files can generate a PHP password hash with `password_hash($password, PASSWORD_DEFAULT)` and set it in `wp-config.php`, before the “stop editing” line. A configured constant takes precedence over the settings screen:

   ```php
   define('PUZZLEPATH_PORTAL_PASSWORD_HASH', 'PASTE_YOUR_PASSWORD_HASH_HERE');
   ```

4. Exclude the query parameter `puzzlepath-discounts` from any CDN/full-page caching. The plugin sends no-store headers, but a cache operating before WordPress must also bypass this URL.
5. Open `https://YOUR-WORDPRESS-DOMAIN/?puzzlepath-discounts=1`. HTTPS is required. If WordPress sits behind a reverse proxy, its HTTPS detection must be configured correctly.
6. Sign in with the separate password, then use your phone browser’s **Add to Home Screen** option.

The initial server installation is required before the portal works. No credentials are bundled. A limited live checkout test verified a 25% code and its expiry rejection; that test code was expired afterwards. No payment was submitted.

## Daily use

Choose or generate a code, choose 1–100% off, set a maximum number of uses (0 means unlimited), and optionally choose an expiry date. Defaults are 25% off and one use. Copy the code or a ready-to-send message after creation. Recent codes show expiry, usage and status; this includes existing WordPress-created codes.

Codes apply to all hunts because the existing booking schema has no per-hunt coupon restriction. Usage counts and booking links remain managed by the booking plugin. This portal does not change checkout or its redemption concurrency behaviour. The copied message links to the WordPress homepage; customers enter their code at checkout, rather than relying on an unsupported auto-apply URL.

Expiry is the end of the chosen day in the WordPress site timezone. Before launch, verify the booking plugin and database use the same timezone conventions: the existing payment integration compares expiry with database `NOW()`, while its apply-code handler uses PHP time parsing. Align those settings if needed.

## Access and maintenance

### Android passkeys (version 1.1.0)

Open the portal in Chrome on your Android phone and sign in using the portal password. Tap **Fingerprint sign-in** beside **Sign out**, confirm the existing portal password, and choose **Enable passkey on this phone**. Approve creation using Android's credential manager and your fingerprint or screen lock. The phone owner must perform this approval. Passkeys require a screen lock and compatible credential provider. The browser can use a PIN instead of biometrics; the portal requires verified user presence but cannot force a particular biometric method.

On subsequent sign-ins choose **Sign in with passkey**. The password remains a fallback. If your phone is lost, change the portal password through **WordPress Settings > Puzzle Path Portal** to revoke both existing sessions and all registered passkeys. Passkeys can sync through the phone's chosen credential provider; the website stores only the public key, credential ID and counters. Up to ten active passkeys can be registered.

Server requirements: PHP 8.0+, OpenSSL and mbstring. WebAuthn verification uses the bundled MIT-licensed lbuchs/WebAuthn v2.2.0 (namespaced to avoid plugin conflicts). Exact origin, RP, challenge, signatures, user verification and user handle are checked. Challenges expire after three minutes, bind to the browser session and password epoch, and are consumed through an atomic database deletion. WP-Cron cleans up unused challenges after four minutes; expiry enforcement does not depend on Cron. New passkey enrollment also requires password reauthentication. WordPress administrator login alone does not enroll a passkey.

Run `php -d extension=openssl -d extension=mbstring tests/passkey-test.php` where extensions are not already enabled. On Windows, point `OPENSSL_CONF` at PHP's `extras/ssl/openssl.cnf` if necessary. These tests use real locally generated ES256 keys to test registration, sign-in and rejection paths; final device enrollment still requires a real phone approval.

Signed, HTTPS-only, HttpOnly cookies last 30 days. Forms have session-bound CSRF protection. Five failed sign-in attempts per remote IP trigger a 15-minute cooldown. Rate limiting uses WordPress transients; use host-level rate limiting as well if the portal is exposed to sustained attacks. The portal sends a restrictive content security policy and prevents framing and search indexing.

Sign out on shared phones. Changing the configured password hash or WordPress authentication salts revokes all portal cookies and registered passkeys. This is a shared owner account; it does not provide individual staff audit trails. Never share the portal password with customers.

## Verification

Run `php tests/core-test.php` from this directory and PHP syntax checks on all PHP files. On a staging installation, verify sign-in, incorrect password lockout, sign-out, a new code, duplicate rejection, copy buttons, expiry, and that checkout applies a limited test coupon and records its `coupon_id`. Check at 320px and 390px viewport widths. Delete the staging test coupon afterwards. No live checkout validation is claimed by the local test suite.

## Home screen icon

The web manifest includes 192px and 512px PNG icons derived from the supplied Puzzle Path artwork. On Android, open the portal in full Chrome and choose **Install and create shortcut**. If Samsung installs it only in the app drawer, find **Path Discounts**, press and hold it, and choose **Add to Home**. The installed app may require a fresh portal sign-in. No offline storage of coupon or account data is enabled.
