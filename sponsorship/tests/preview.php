<?php
// Render-only local QA. Never package or deploy this file. Submission is unavailable
// unless the browser test explicitly intercepts requests with synthetic fixtures.
define('ABSPATH', __DIR__);
function esc_url($v) { return htmlspecialchars($v, ENT_QUOTES); }
function esc_attr($v) { return htmlspecialchars($v, ENT_QUOTES); }
function esc_html($v) { return htmlspecialchars($v, ENT_QUOTES); }
function home_url($path) { return 'https://puzzlepath.com.au' . $path; }
$asset_url = '/puzzlepath-sponsorship/assets/'; $endpoint = isset($_GET['qa']) ? '/tests/endpoint.php' : '/tests/unavailable.php';
$privacy_url = 'https://example.invalid/privacy-policy'; $organisations = [];
?><!doctype html><html lang="en-AU"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sponsorship — local review</title><link rel="stylesheet" href="/puzzlepath-sponsorship/assets/sponsorship.css"><style>body{margin:0}header,footer{text-align:center;padding:25px;background:#eee9d5}header a{display:inline-block;margin:8px 15px;color:#154f4b;font-weight:bold}.preview-note{padding:8px;background:#154f4b;color:white;text-align:center;font-size:13px}</style><script src="/puzzlepath-sponsorship/assets/sponsorship.js" defer></script></head><body class="pps-page"><div class="preview-note">LOCAL REVIEW · Submissions are disabled here. No live enquiry is sent.</div><header><nav><a href="https://puzzlepath.com.au/">Home</a><a href="https://puzzlepath.com.au/puzzle-path-upcoming-adventures/">Upcoming Adventures</a><a href="https://puzzlepath.com.au/about/">About</a><a href="https://puzzlepath.com.au/contact/">Contact</a><a href="#sponsorship" aria-current="page">Sponsorship</a></nav></header><main><?php require __DIR__ . '/../puzzlepath-sponsorship/view.php'; ?></main><footer>Puzzle Path · Discover Queensland one clue at a time.</footer></body></html>
