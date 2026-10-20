<?php
defined('ABSPATH') || exit;

function ppdp_b64($bytes) { return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='); }
function ppdp_unb64($value, $max = 65536) {
    if (!is_string($value) || !$value || strlen($value) > $max || !preg_match('/^[A-Za-z0-9_-]+$/D', $value)) throw new InvalidArgumentException('Invalid passkey response.');
    $bytes = base64_decode(strtr($value, '-_', '+/'), true);
    if ($bytes === false || ppdp_b64($bytes) !== $value) throw new InvalidArgumentException('Invalid passkey response.');
    return $bytes;
}
function ppdp_origin() {
    $parts = wp_parse_url(home_url('/'));
    return 'https://' . strtolower($parts['host']) . (isset($parts['port']) && $parts['port'] !== 443 ? ':' . $parts['port'] : '');
}
function ppdp_client_data($encoded, $origin) {
    $raw = ppdp_unb64($encoded, 8192);
    $data = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
    // The library permits RP subdomains; this portal requires its exact origin.
    if (!is_array($data) || ($data['origin'] ?? '') !== $origin || ($data['crossOrigin'] ?? false) !== false || isset($data['topOrigin'])) {
        throw new InvalidArgumentException('Passkey origin did not match this portal.');
    }
    return $raw;
}
function ppdp_passkey_server() {
    if (PHP_VERSION_ID < 80000 || !extension_loaded('openssl') || !extension_loaded('mbstring')) throw new RuntimeException('Passkeys require PHP 8, OpenSSL and mbstring on the server.');
    require_once __DIR__ . '/vendor/webauthn/src/WebAuthn.php';
    return new \PuzzlePathPortal\Vendor\WebAuthn\WebAuthn('Puzzle Path Discount Portal', wp_parse_url(home_url('/'), PHP_URL_HOST), ['none'], true);
}
function ppdp_passkey_epoch($key) { return hash_hmac('sha256', 'passkey-epoch', $key); }
function ppdp_passkey_owner($key) { return hash_hmac('sha256', 'portal-owner', $key, true); }
function ppdp_passkey_records($key) {
    global $wpdb;
    $names = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like('ppdp_credential_') . '%'));
    $records = [];
    foreach ($names as $name) {
        $record = get_option($name);
        if (is_array($record) && hash_equals(ppdp_passkey_epoch($key), $record['epoch'] ?? '')) $records[$name] = $record;
    }
    return $records;
}
// Compare-and-delete is atomic even with concurrent requests or object caching.
function ppdp_consume_option($name, $expected) {
    global $wpdb;
    $deleted = $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $name, maybe_serialize($expected)));
    wp_cache_delete($name, 'options');
    return $deleted === 1;
}
add_action('ppdp_expire_passkey_challenge', function ($name, $expected) {
    if (strpos($name, 'ppdp_challenge_') === 0) ppdp_consume_option($name, $expected);
}, 10, 2);
function ppdp_issue_challenge($challenge, $purpose, $binding, $key) {
    $id = bin2hex(random_bytes(24));
    $name = 'ppdp_challenge_' . $id;
    $record = ['challenge' => ppdp_b64($challenge), 'purpose' => $purpose, 'binding' => hash_hmac('sha256', $binding, $key), 'expires' => time() + 180];
    if (!add_option($name, $record, '', false)) throw new RuntimeException('Could not start passkey verification.');
    wp_schedule_single_event(time() + 240, 'ppdp_expire_passkey_challenge', [$name, $record]);
    return $id;
}
function ppdp_take_challenge($id, $purpose, $binding, $key) {
    if (!is_string($id) || !preg_match('/^[a-f0-9]{48}$/D', $id)) throw new InvalidArgumentException('Start passkey verification again.');
    $name = 'ppdp_challenge_' . $id;
    $record = get_option($name);
    if (!is_array($record) || ($record['expires'] ?? 0) <= time() || ($record['purpose'] ?? '') !== $purpose || !hash_equals($record['binding'] ?? '', hash_hmac('sha256', $binding, $key))) throw new InvalidArgumentException('Passkey request expired. Please try again.');
    if (!ppdp_consume_option($name, $record)) throw new InvalidArgumentException('This passkey request has already been used.');
    return ppdp_unb64($record['challenge']);
}
function ppdp_passkey_request($input, $signed_in, $binding, $key, $password_hash) {
    $action = $input['action'] ?? '';
    if (!in_array($action, ['passkey_register_options', 'passkey_register_finish', 'passkey_login_options', 'passkey_login_finish'], true)) throw new InvalidArgumentException('Unknown passkey action.');
    $register = strpos($action, 'passkey_register_') === 0;
    if ($register && !$signed_in) throw new InvalidArgumentException('Sign in with your password before adding a passkey.');
    $server = ppdp_passkey_server();
    if (substr($action, -8) === '_options') {
        $rate_key = 'ppdp_passkey_' . hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $attempts = (int)get_transient($rate_key);
        if ($attempts >= 15) throw new InvalidArgumentException('Too many requests. Please wait 10 minutes.');
        set_transient($rate_key, $attempts + 1, 600);
        if ($register) {
            // Explicit password reauthentication prevents a stolen long-lived session enrolling a new key.
            if (!is_string($input['password'] ?? null) || !password_verify($input['password'], $password_hash)) throw new InvalidArgumentException('Confirm your portal password to add a passkey.');
            $records = ppdp_passkey_records($key);
            if (count($records) >= 10) throw new InvalidArgumentException('Ten passkeys are already registered. Reset the portal password in WordPress to revoke them.');
            $ids = array_map(function ($record) { return ppdp_unb64($record['id']); }, array_values($records));
            $args = $server->getCreateArgs(ppdp_passkey_owner($key), 'puzzlepath-owner', 'Puzzle Path Owner', 120, true, 'required', false, $ids);
        } else {
            // Discoverable credentials: no credential IDs are disclosed to unauthenticated visitors.
            $args = $server->getGetArgs([], 120, true, true, true, true, true, 'required');
        }
        return ['options' => $args, 'request' => ppdp_issue_challenge($server->getChallenge()->getBinaryString(), $register ? 'register' : 'login', $binding, $key)];
    }
    $challenge = ppdp_take_challenge($input['request'] ?? '', $register ? 'register' : 'login', $binding, $key);
    $client = ppdp_client_data($input['clientDataJSON'] ?? '', ppdp_origin());
    $id = ppdp_unb64($input['id'] ?? '', 2048);
    $name = 'ppdp_credential_' . hash('sha256', $id);
    if ($register) {
        if (count(ppdp_passkey_records($key)) >= 10) throw new InvalidArgumentException('Passkey limit reached.');
        $result = $server->processCreate($client, ppdp_unb64($input['attestationObject'] ?? ''), $challenge, true, true, true);
        if (!hash_equals($id, $result->credentialId)) throw new InvalidArgumentException('Credential ID did not match.');
        $record = ['id' => ppdp_b64($id), 'public_key' => $result->credentialPublicKey, 'counter' => (int)$result->signatureCounter, 'epoch' => ppdp_passkey_epoch($key), 'created' => time()];
        $existing = get_option($name);
        if ($existing && ($existing['epoch'] ?? '') !== $record['epoch']) ppdp_consume_option($name, $existing);
        if (!add_option($name, $record, '', false)) throw new InvalidArgumentException('This passkey is already registered.');
        return ['registered' => true];
    }
    $record = get_option($name);
    if (!is_array($record) || !hash_equals(ppdp_passkey_epoch($key), $record['epoch'] ?? '') || !hash_equals($record['id'], ppdp_b64($id))) throw new InvalidArgumentException('Passkey not recognised. Use your portal password.');
    if (!hash_equals(ppdp_passkey_owner($key), ppdp_unb64($input['userHandle'] ?? '', 256))) throw new InvalidArgumentException('Passkey owner did not match.');
    $server->processGet($client, ppdp_unb64($input['authenticatorData'] ?? '', 8192), ppdp_unb64($input['signature'] ?? '', 2048), $record['public_key'], $challenge, (int)$record['counter'], true, true);
    $record['counter'] = (int)$server->getSignatureCounter();
    $record['last_used'] = time();
    update_option($name, $record, false);
    return ['authenticated' => true];
}
