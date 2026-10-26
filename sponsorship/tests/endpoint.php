<?php
// Local-only QA adapter; never included in the production ZIP.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(403); exit; }
define('PPS_TEST_LIBRARY', true); require __DIR__ . '/backend.php';
$GLOBALS['test_origin'] = 'http://127.0.0.1:8025';
$wpdb = new Database(__DIR__ . '/../review/test-enquiries.sqlite');
try {
    if (($_POST['action'] ?? '') === 'pps_bootstrap') pps_bootstrap();
    elseif (($_POST['action'] ?? '') === 'pps_submit') pps_submit();
    else pps_fail('Local review has no active backend.', 503);
} catch (Response $r) { http_response_code($r->status); header('Content-Type: application/json'); echo json_encode($r->body); }
