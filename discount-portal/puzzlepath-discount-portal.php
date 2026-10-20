<?php
/**
 * Plugin Name: Puzzle Path Discount Portal
 * Description: Private mobile coupon creation connected to PuzzlePath Booking.
 * Version: 1.1.2
 * Requires PHP: 8.0
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/portal-core.php';
require_once __DIR__ . '/portal-settings.php';
require_once __DIR__ . '/portal-passkeys.php';

add_action('template_redirect', 'ppdp_route', 0);
function ppdp_route() {
    if (!isset($_GET['puzzlepath-discounts'])) return;
    if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
    nocache_headers();
    header('Cache-Control: no-store, private, max-age=0');
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    $nonce = bin2hex(random_bytes(16));
    header("Content-Security-Policy: default-src 'none'; connect-src 'self'; img-src 'self'; manifest-src 'self'; style-src 'nonce-$nonce'; script-src 'nonce-$nonce'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
    header('Permissions-Policy: publickey-credentials-create=(self), publickey-credentials-get=(self)');
    if (!is_ssl()) wp_die('This private portal requires HTTPS.', 'Secure connection required', ['response' => 403]);
    $password_hash = ppdp_password_hash();
    if (!$password_hash || !password_get_info($password_hash)['algo']) {
        wp_die('The portal password has not been configured. An administrator can set it under Settings > Puzzle Path Portal.', 'Portal setup required', ['response' => 503]);
    }
    global $wpdb;
    $table = $wpdb->prefix . 'pp_coupons';
    $key = hash_hmac('sha256', $password_hash, wp_salt('auth'));
    $url = home_url('/?puzzlepath-discounts=1');
    $cookie = 'ppdp_access';
    $token = $_COOKIE[$cookie] ?? '';
    $signed_in = ppdp_valid_token($token, $key);
    // A separate, short-lived signed token protects the sign-in form too.
    $form_token = $_COOKIE['ppdp_form'] ?? '';
    if (!ppdp_valid_token($form_token, $key)) {
        $form_token = ppdp_token(time() + 3600, bin2hex(random_bytes(32)), $key);
        ppdp_cookie('ppdp_form', $form_token, time() + 3600);
    }
    $csrf = hash_hmac('sha256', $signed_in ? $token : $form_token, $key);
    $error = '';
    $created = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = wp_unslash($_POST);
        if (is_string($input['action'] ?? null) && strpos($input['action'], 'passkey_') === 0) {
            if (!is_string($input['csrf'] ?? null) || !hash_equals($csrf, $input['csrf']) || ($_SERVER['HTTP_ORIGIN'] ?? '') !== ppdp_origin()) {
                wp_send_json(['error' => 'Your session expired. Refresh the page and try again.'], 403);
            }
            if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 100000) wp_send_json(['error' => 'Passkey response is too large.'], 413);
            try {
                $result = ppdp_passkey_request($input, $signed_in, $signed_in ? $token : $form_token, $key, $password_hash);
                if (!empty($result['authenticated'])) {
                    $expires = time() + 30 * DAY_IN_SECONDS;
                    ppdp_cookie($cookie, ppdp_token($expires, bin2hex(random_bytes(32)), $key), $expires);
                }
                wp_send_json($result);
            } catch (InvalidArgumentException $e) {
                wp_send_json(['error' => $e->getMessage()], 400);
            } catch (Throwable $e) {
                wp_send_json(['error' => 'Passkey verification could not be completed. Please try again or use your portal password.'], 400);
            }
        }
        if (!is_string($input['csrf'] ?? null) || !hash_equals($csrf, $input['csrf'])) {
            $error = 'Your form expired. Please refresh and try again.';
        } elseif (($input['action'] ?? '') === 'login' && !$signed_in) {
            $rate_key = 'ppdp_login_' . hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $attempts = (int)get_transient($rate_key);
            if ($attempts >= 5) {
                $error = 'Too many attempts. Please try again in 15 minutes.';
            } else {
                set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
                if (is_string($input['password'] ?? null) && password_verify($input['password'], $password_hash)) {
                    delete_transient($rate_key);
                    $expires = time() + 30 * DAY_IN_SECONDS;
                    ppdp_cookie($cookie, ppdp_token($expires, bin2hex(random_bytes(32)), $key), $expires);
                    wp_safe_redirect($url, 303); exit;
                }
                $error = 'That password was not recognised.';
            }
        } elseif ($signed_in) {
            if (($input['action'] ?? '') === 'logout') {
                ppdp_cookie($cookie, '', time() - 3600);
                wp_safe_redirect($url, 303); exit;
            }
            if (($input['action'] ?? '') === 'create') {
                try {
                    foreach (['code', 'percent', 'uses', 'expiry'] as $field) {
                        if (isset($input[$field]) && !is_string($input[$field])) throw new InvalidArgumentException('Please check the form values.');
                    }
                    $data = ppdp_validate($input, wp_timezone());
                    $previous = $wpdb->suppress_errors(true);
                    $inserted = $wpdb->insert($table, $data, ['%s', '%d', '%d', '%d', '%s']);
                    $wpdb->suppress_errors($previous);
                    if (!$inserted) {
                        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM `$table` WHERE code = %s", $data['code']));
                        throw new RuntimeException($exists ? 'That code already exists. Choose a different code.' : 'The code could not be saved. Check that PuzzlePath Booking is installed and try again.');
                    }
                    wp_safe_redirect(add_query_arg('created', (int)$wpdb->insert_id, $url), 303); exit;
                } catch (Exception $e) { $error = $e->getMessage(); }
            }
        } else { $error = 'Please sign in to create a code.'; }
    }
    $rows = [];
    if ($signed_in) {
        $rows = $wpdb->get_results("SELECT id, code, discount_percent, max_uses, times_used, expires_at FROM `$table` ORDER BY id DESC LIMIT 30", ARRAY_A);
        if ($rows === null) $error = 'Unable to load codes. Check the PuzzlePath Booking database connection.';
        if (isset($_GET['created'])) $created = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table` WHERE id = %d", absint($_GET['created'])), ARRAY_A);
    }
    foreach (['code', 'percent', 'uses', 'expiry'] as $field) {
        if (isset($input[$field]) && !is_string($input[$field])) $input[$field] = '';
    }
    require __DIR__ . '/portal-view.php';
    exit;
}
function ppdp_cookie($name, $value, $expiry) {
    setcookie($name, $value, ['expires' => $expiry, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']);
}
