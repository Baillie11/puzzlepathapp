# Bookings tester invitation

`tester-invitation.php` contains the requested replacement for the tester key notice previously added directly to the Astra theme's `functions.php`.

It renders only on `puzzlepath_page_puzzlepath-bookings`. A small **Goto Tester link** at the top jumps to the invitation section below the bookings content. The section includes the existing key, instructions, a selectable invitation URL, and a **Copy invitation link** button with clipboard fallback and an accessible status message.

The key is read from `puzzlepath_tester_access_key`; no credentials are bundled. Registration behavior remains owned by the booking plugin.

## Installation

Back up the active theme file. Remove the old anonymous `admin_notices` callback that displays the tester key, then include this file once from the active child theme or an appropriate site customization plugin. Do not add it alongside the previous callback. A child theme or site plugin avoids losing the customization during an Astra update.

The bottom-of-page layout is recorded here as the requested source revision. Its live deployment was interrupted during the phone-shortcut work and has not been verified. The earlier bookings-only notice and copy button were deployed and verified.

After installation, verify the top anchor, bottom section, mobile copy control, and absence of the tester key on Dashboard and Coupons.
