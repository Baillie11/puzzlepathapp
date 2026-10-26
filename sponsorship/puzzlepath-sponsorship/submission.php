<?php
defined('ABSPATH') || exit;
function pps_fail(string $message, int $status = 400, array $errors = []): void { wp_send_json_error(['message' => $message, 'errors' => $errors], $status); }
function pps_rate(string $kind, int $seconds, int $limit): bool {
    global $wpdb;
    // Trust REMOTE_ADDR only. Do not retain raw IP addresses or trust forwarded headers.
    $bucket = hash_hmac('sha256', $kind . '|' . ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . floor(time() / $seconds), wp_salt('auth'));
    $result = $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}pps_rate_limits (bucket,attempts,expires_at) VALUES (%s,1,%d) ON DUPLICATE KEY UPDATE attempts=attempts+1", $bucket, time() + $seconds * 2));
    if ($result === false) pps_fail('We could not connect to our enquiry service. Please try again later.', 503);
    return (int)$wpdb->get_var($wpdb->prepare("SELECT attempts FROM {$wpdb->prefix}pps_rate_limits WHERE bucket=%s", $bucket)) <= $limit;
}
function pps_origin(): string {
    $parts = wp_parse_url(home_url());
    return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
}
function pps_guard(): void {
    nocache_headers();
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') pps_fail('Please submit the form from our website.', 405);
    if (!is_ssl()) pps_fail('A secure connection is required.', 403);
    if (($_SERVER['HTTP_ORIGIN'] ?? '') !== pps_origin()) pps_fail('Please submit the form from our website.', 403);
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 8192) pps_fail('This request is too large.', 413);
    if (!get_privacy_policy_url()) pps_fail('Sponsorship enquiries are temporarily unavailable. Please contact Andrew Baillie through our Contact page.', 503);
}
function pps_token(string $request, int $issued, string $session): string {
    return hash_hmac('sha256', $request . '|' . $issued . '|' . $session, wp_salt('nonce'));
}
function pps_bootstrap(): void {
    pps_guard();
    if (!pps_rate('start', 600, 30)) pps_fail('Please wait a few minutes before trying again.', 429);
    $session = $_COOKIE['pps_session'] ?? '';
    if (!is_string($session) || !preg_match('/^[a-f0-9]{64}$/', $session)) $session = bin2hex(random_bytes(32));
    setcookie('pps_session', $session, ['expires' => time() + DAY_IN_SECONDS, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
    $request = bin2hex(random_bytes(16)); $issued = time();
    wp_send_json_success(['request_id' => $request, 'issued' => $issued, 'token' => pps_token($request, $issued, $session)]);
}
function pps_submit(): void {
    pps_guard();
    $input = wp_unslash($_POST);
    foreach (['request_id', 'issued', 'token', 'company_fax'] as $field) if (!is_scalar($input[$field] ?? '')) pps_fail('Please reload the page and try again.');
    $request = (string)($input['request_id'] ?? ''); $issued = (int)($input['issued'] ?? 0); $token = (string)($input['token'] ?? '');
    $session = $_COOKIE['pps_session'] ?? '';
    if (!is_string($session) || !preg_match('/^[a-f0-9]{64}$/', $session) || !preg_match('/^[a-f0-9]{32}$/', $request) || $issued > time() || time() - $issued > 7200 || !hash_equals(pps_token($request, $issued, $session), $token)) pps_fail('Your form session has expired. Reload the page and try again.', 403);
    if (($input['company_fax'] ?? '') !== '' || time() - $issued < 2) pps_fail('Please wait a moment and try again.');
    [$data, $errors] = pps_validate($input);
    if ($errors) pps_fail('Please check the highlighted fields.', 422, $errors);
    global $wpdb;
    $table = $wpdb->prefix . 'pps_applications';
    $payload_hash = hash_hmac('sha256', wp_json_encode($data), wp_salt('auth'));
    $existing = $wpdb->get_row($wpdb->prepare("SELECT reference_id,payload_hash FROM $table WHERE request_id=%s", $request));
    if ($existing) {
        if (!hash_equals($existing->payload_hash, $payload_hash)) pps_fail('This form was already submitted. Reload to start a new enquiry.', 409);
        wp_send_json_success(['reference_id' => $existing->reference_id]);
    }
    if (!pps_rate('submit', 3600, 5)) pps_fail('Too many requests have been made. Please try again later or use our Contact page.', 429);
    $reference = wp_generate_uuid4(); $now = current_time('mysql', true);
    $record = array_merge($data, ['reference_id' => $reference, 'request_id' => $request, 'payload_hash' => $payload_hash, 'status' => 'new', 'source' => 'website_sponsorship_form', 'created_at' => $now, 'updated_at' => $now]);
    // UNIQUE request_id prevents simultaneous requests from recording duplicates.
    $previous = $wpdb->suppress_errors();
    $saved = $wpdb->insert($table, $record);
    $wpdb->suppress_errors($previous);
    if ($saved === false) {
        $existing = $wpdb->get_row($wpdb->prepare("SELECT reference_id,payload_hash FROM $table WHERE request_id=%s", $request));
        if ($existing && hash_equals($existing->payload_hash, $payload_hash)) wp_send_json_success(['reference_id' => $existing->reference_id]);
        pps_fail('Your enquiry could not be saved. Please try again. Your details are still in the form.', 503);
    }
    // Future private management integrations may subscribe; no email service is added.
    do_action('pps_application_recorded', $reference);
    wp_send_json_success(['reference_id' => $reference]);
}
foreach (['pps_bootstrap', 'pps_submit'] as $action) {
    add_action('wp_ajax_' . $action, $action);
    add_action('wp_ajax_nopriv_' . $action, $action);
}
