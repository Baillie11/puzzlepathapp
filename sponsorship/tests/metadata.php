<?php
define('ABSPATH', __DIR__); function add_action(...$args) {}
require __DIR__ . '/../puzzlepath-sponsorship/metadata.php';
$old = '<html><head><title>Theme</title><title>SEO plugin</title><meta name="description" content="General"><meta property="og:title" content="General"><meta name="twitter:description" content="General"><meta name="robots" content="index,follow"><script>window.example=1;</script></head><body><p>Original content</p></body></html>';
$new = '<title>Sponsorship</title><meta name="description" content="Community vouchers">';
$result = pps_normalise_head($old, $new);
foreach ([substr_count($result, '<title>') === 1, substr_count($result, 'name="description"') === 1, !str_contains($result, 'content="General"'), str_contains($result, 'name="robots"'), str_contains($result, '<script>window.example=1;</script>'), str_contains($result, '<body><p>Original content</p></body>'), pps_normalise_head($result, $new) === $result] as $ok) if (!$ok) throw new Exception('Metadata normalisation regression');
echo "PASS: 7 metadata checks.\n";
