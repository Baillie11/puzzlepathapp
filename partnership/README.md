# Puzzle Path Partnership

WordPress page at `/partnership/`, with approved Koko artwork, current cream/teal styling, partnership benefits and an expression-of-interest form. Enquiries are sent to `info@puzzlepath.com.au` using WordPress `wp_mail`; replies go to the validated applicant email.

## Install

Upload the ZIP containing only `puzzlepath-partnership/` in WordPress Plugins → Add New → Upload Plugin, then activate. Activation publishes the page and adds Partnership to the existing primary/mobile navigation using the theme's menu walker. Existing matching menu links are not duplicated. An unrelated page already occupying `/partnership/` is preserved and an administrator notice explains the conflict.

The existing theme owns navigation and footer markup. The shortcode is `[puzzlepath_partnership]`. Deactivation removes the dynamic menu entry but preserves the page; unpublish the page before deactivation to avoid exposing the shortcode.

## Form

Required business name, location, contact name, valid email and contact consent are validated server-side. Phone is required when phone contact is selected. Interests and optional reward choices are checked against an allowlist. A nonce, honeypot and short-lived hashed email/IP rate counters protect submissions. The form page opts out of WordPress/LiteSpeed page caching to keep its nonce fresh. If a CDN separately caches HTML, exclude `/partnership/` there too.

Enquiry content is not saved to a plugin database. The receiving mailbox and the site's mail infrastructure may retain it. A successful submission means WordPress's configured mail transport accepted the message; it does not prove inbox delivery. Failure keeps retry available. No automatic marketing registration or applicant confirmation email is sent.

## Checks

Run `php tests/submission.php` from this directory. The adapter checks required/invalid fields, nonce, honeypot, phone selection, invalid options, mail failure/retry and duplicate rate limiting without sending real email. Run PHP lint for both plugin PHP files and `node --check puzzlepath-partnership/assets/partnership.js`.

The separate `index.php` local preview is not part of the installable plugin. It deliberately disables email sending. Browser verification covered required fields, consent, phone conditional validation, successful local validation and narrow-screen layout. Live theme integration and actual email delivery must be verified after installation.

## Artwork

`assets/koko-transparent.png` reuses the approved transparent Koko artwork already served by the Puzzle Path Contact page at `/wp-content/plugins/puzzlepath-booking-look/assets/koko-transparent.png`.
