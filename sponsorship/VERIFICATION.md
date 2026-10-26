# Sponsorship verification — 26 October 2026

## Implemented and checked

- Read live `/contact/` and `/about/`, existing theme/navigation and Contact look stylesheet. Correct existing adventure route is `/puzzle-path-upcoming-adventures/`.
- All plugin PHP files pass `php -l`; JavaScript passes `node --check`. No package manifest, TypeScript or compiled build exists for this PHP plugin; no dependency install is needed.
- `php sponsorship/tests/markup.php`: 45 rendered HTML checks, including unique IDs, label/anchor targets, final CTA, adventure links, nine FAQs and default contact controls. This caught and corrected a missing quote in the last FAQ link before release.
- `php sponsorship/tests/backend.php`: 41 checks using the real production validation/submission/privacy functions with an explicitly test-only SQLite adapter for WordPress database APIs. Covers normalisation, optional website, email, Australian phone formats, array/malformed inputs, markup removal, both contact methods, persisted records after connection restart, UTC metadata/source/status, duplicate retries, changed-payload rejection, insert-race recovery, rate limiting, origin, HTTPS, cookie, expiry, honeypot, minimum fill time, missing policy, database failure, application insert failure, privacy export/erasure and recipient publication rules.
- Browser: required-field errors focus the first invalid field; phone is revealed/required only for Phone call and cleared/disabled for Email. Invalid phone is rejected by the real production handler in the local adapter. Synthetic email and phone enquiries saved in the local SQLite test database and returned unique references. No live enquiries were submitted.
- FAQ opens and closes with Enter; all nine are native details/summary controls. Both request links focus and scroll to the form. Reduced-motion preference disables smooth scrolling and button movement.
- Approved Koko source has an alpha channel: 808,739 fully transparent pixels. Optimised WebP is 152,302 bytes (640×960). Full character and footprints remain visible without an opaque rectangle.
- Golden Ticket is a CSS information illustration, labelled visual concept/not redeemable, with no redemption code.
- No organisations are displayed without verified public approval.

## Local preview

Run `php -S 127.0.0.1:8025 -t sponsorship`, then visit `http://127.0.0.1:8025/tests/preview.php`. This shows a simplified review header/footer; production uses the existing WordPress theme. Normal preview submissions are disabled. The `?qa=1` test variant uses a local-only SQLite adapter, synthetic test details and no external service; never deploy `tests/` or `review/`.

Browser screenshots are in `review/`. Desktop, tablet and mobile widths were inspected, including narrow mobile. Browser zoom caused requested 1440/768/390/320 viewport settings to report approximately 1600/854/433/356 CSS pixels; layout checks use actual CSS viewport dimensions. No horizontal overflow was observed. QA browser console had no JavaScript errors.

## Production deployment

- Installed and activated through WordPress admin; updated successfully to 1.0.1. Published page 1994 is live at https://puzzlepath.com.au/sponsorship/.
- Verified Sponsorship in desktop and expanded mobile navigation and the live footer block. Mobile viewport 390px has no horizontal overflow.
- Corrected Astra's article background and island stacking. Live CSS reports version 1.0.1 and cream background rgb(248,245,233).
- Corrected conflicting theme/Click SEO metadata with a page-scoped head normaliser. Verified one correct title and one description live; seven additional metadata regression checks pass.
- Privacy Policy page 1996 was saved as a draft at the user's request, with review notes. It is not published. The live form correctly reports temporary unavailability until an approved policy is published and selected.
- No live enquiry was submitted. Actual MySQL record persistence and sitemap inclusion remain unverified; local SQLite tests do not establish production persistence. Run a labelled live test after policy approval.
- A ClickBackup job was started but completion has not been confirmed. Do not treat it as a verified restore point.

Existing unrelated working-tree changes were preserved. Deployment source was isolated in a managed worktree based on current origin/main.
