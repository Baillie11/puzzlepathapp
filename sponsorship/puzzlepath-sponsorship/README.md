# Puzzle Path Community Sponsorship

WordPress plugin for `/sponsorship`. Built within the existing Puzzle Path repository; no booking, checkout, pricing, quest or quiz files were changed.

## Installation

1. Back up WordPress and its database. Upload `puzzlepath-sponsorship.zip` through Plugins → Add New → Upload Plugin, then activate it. Requires PHP 8.0+, WordPress and HTTPS.
2. Activation creates two tables using the site's existing WordPress database connection and a published `/sponsorship` page containing `[puzzlepath_sponsorship]`. If that slug already exists with unrelated content, it is preserved; an admin notice explains how to assign the shortcode and reactivate. No external service or credential is required.
3. Select the site's published Privacy Policy in Settings → Privacy. Submissions deliberately remain unavailable if that setting has no published policy. Review the suggested sponsorship privacy text in WordPress's privacy-policy guide and set an operational retention period. No public policy was found in the live site's public page search during this build.
4. Purge the site's page cache. Keep `wp-admin/admin-ajax.php` uncached. Confirm the active Astra primary/mobile menu and footer display Sponsorship. Menu entries use existing WordPress walkers. Footer integration supports Astra copyright and the live copyright block widget.
5. Submit a clearly labelled test enquiry through both contact options and verify the corresponding rows in the private database. Test database outage handling on staging. Local tests do not establish production connectivity.

Activation normally adds the published page to WordPress's existing page sitemap; no separate sitemap is created. Check any SEO plugin exclusion settings. Metadata supports default WordPress, Yoast and Rank Math descriptions. Existing header, footer, mobile navigation and their accessibility behavior remain owned by the theme.

Deactivation removes dynamic navigation links and scheduled rate-limit cleanup, but preserves the page and enquiries. Before deactivation, unpublish the sponsorship page to avoid displaying an inactive shortcode. Uninstall never silently deletes enquiries.

## Files and design

- `puzzlepath-sponsorship.php`: activation/schema, shortcode, route page, assets, metadata and navigation integration.
- `view.php`: semantic page, short application form, nine native keyboard-accessible FAQ disclosures and Golden Ticket concept (explicitly not redeemable).
- `assets/sponsorship.css`: scoped styles matching live `/contact/` and `/about/`: `#f8f5e9` cream, `#154f4b` teal, `#d9e3b8` green, Trebuchet MS, rounded panels, pill buttons and subtle shadows.
- `assets/community-island.svg`: existing live Contact page island artwork, reused locally.
- `assets/koko-transparent.webp`: approved live Contact page Koko artwork, resized to 640×960, about 149 KiB. `koko-transparent.png` supplies the Open Graph image. Both retain actual alpha transparency; no blending/masking trick is used.
- `core.php`, `submission.php`, `privacy.php`: validation, durable recording, anti-spam, and WordPress privacy export/erasure integration.
- `data/organisations.php`: structured recipient source, currently empty. No unapproved relationship names, logos, links or endorsements are published.

The approved artwork was sourced from `https://puzzlepath.com.au/wp-content/plugins/puzzlepath-booking-look/assets/koko-transparent.png`; the original has 808,739 fully transparent pixels of 1,572,864 pixels. The image-generation attempt was not used because the existing approved transparent asset was located. Its built-in tool prompt was: remove only the supplied Koko image's white background and ground shadow, preserve her complete appearance, pose and accessories, retain clean footprints, and produce genuine transparent alpha with no vignette or redesign.

## Private records and future integration

`{wp_prefix}pps_applications` stores:

| Field | Purpose |
| --- | --- |
| `id` | Internal numeric primary key |
| `reference_id` | Stable UUID, unique; returned to the applicant |
| `request_id` | Unique submission key for idempotent retries |
| `payload_hash` | Server-keyed HMAC of normalised input, detects changed retries |
| `organisation_name`, `website` | Organisation and optional normalised HTTP(S) website |
| `contact_name`, `email`, `contact_method`, `phone` | Contact information; phone stored only for phone requests |
| `status` | Initially `new` |
| `source` | `website_sponsorship_form` |
| `created_at`, `updated_at` | UTC database timestamps |

The schema is independent of the page renderer. A future private management plugin can query this table through WordPress and subscribe to `pps_application_recorded($reference_id)`. Organisation data can be supplied through the `pps_organisations` filter. A card requires `sponsorship_status=confirmed`, boolean `public_display_enabled=true`, and a recorded `publication_authorised_at`. Only supply verified website URLs, authorised logos and descriptions.

No public listing/read endpoint exists. POST responses contain only a reference ID or validation errors. There is no inbox, notification service, marketing subscription, approval workflow or voucher-generation system. Andrew must review the private database until the separate management application is implemented; storage alone does not notify him.

The AJAX endpoints use HTTPS, exact same-origin validation, an HttpOnly SameSite cookie and a signed two-hour form token. Fresh tokens are fetched separately to tolerate page caching. Server validation strips markup and bounds field lengths; SQL writes use WordPress escaping/preparation. Honeypot, minimum fill time and atomic per-IP hashed rate counters reduce spam. Raw IP addresses are not retained. Short-lived rate counters are cleaned through WP-Cron. Unique request IDs prevent repeat-click/network-retry duplicates, including simultaneous inserts. Database failure never returns success.

WordPress's administrator-only Tools → Export/Erase Personal Data workflows include these records. Enquiries have no automatic expiry: choose and apply retention appropriate to the business, including backups. This implementation does not represent a legal compliance certification.

## Verification and release limitations

See `../VERIFICATION.md` in the source workspace for results and commands. The release ZIP contains only this plugin folder; local preview, synthetic test records, screenshots and test adapters are excluded.

Version 1.0.1 was installed and activated on puzzlepath.com.au on 26 October 2026. The published `/sponsorship/` page (1994), desktop/mobile navigation, footer link, scoped styling and page metadata were verified live. `metadata.php` normalises this page's head because the live theme and Click SEO otherwise emit conflicting metadata.

Privacy Policy page 1996 is saved as an unpublished draft for Andrew's review. The form correctly remains unavailable until an approved policy is published and selected in Settings → Privacy. The review copy is `../privacy-policy-draft.html`. Live MySQL persistence and sitemap inclusion remain unverified; run a labelled live enquiry after policy approval. No live enquiry was submitted. Local previews never accept real enquiries.
