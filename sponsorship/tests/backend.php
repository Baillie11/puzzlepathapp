<?php
// Local integration harness: real production handlers + SQLite adapter, not WordPress/MySQL.
define('ABSPATH', __DIR__ . '/'); define('DAY_IN_SECONDS', 86400); define('ARRAY_A', 'ARRAY_A');
class Response extends Exception { public function __construct(public array $body, public int $status) { parent::__construct('response'); } }
function wp_send_json_error($data, $status = 400) { throw new Response(['success' => false, 'data' => $data], $status); }
function wp_send_json_success($data) { throw new Response(['success' => true, 'data' => $data], 200); }
function add_action(...$args) {} function add_filter(...$args) {} function do_action(...$args) {}
function nocache_headers() {} function is_ssl() { return $GLOBALS['ssl'] ?? true; }
function get_privacy_policy_url() { return $GLOBALS['privacy'] ?? 'https://example.test/privacy-policy/'; }
function home_url() { return $GLOBALS['test_origin'] ?? 'https://example.test'; } function wp_parse_url($s) { return parse_url($s); }
function wp_salt($type) { return 'test-only-not-a-real-secret-' . $type; }
function wp_unslash($v) { return $v; } function wp_json_encode($v) { return json_encode($v); }
function wp_generate_uuid4() { $h = bin2hex(random_bytes(16)); return substr($h,0,8).'-'.substr($h,8,4).'-4'.substr($h,13,3).'-a'.substr($h,17,3).'-'.substr($h,20,12); }
function current_time(...$args) { return gmdate('Y-m-d H:i:s'); }
class Database {
    public string $prefix = 'test_'; public PDO $db; public bool $fail = false; public bool $failInsert = false; public bool $race = false;
    function __construct($path) {
        $this->db = new PDO('sqlite:' . $path); $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('CREATE TABLE IF NOT EXISTS test_pps_rate_limits (bucket TEXT PRIMARY KEY,attempts INTEGER,expires_at INTEGER)');
        $this->db->exec('CREATE TABLE IF NOT EXISTS test_pps_applications (id INTEGER PRIMARY KEY,reference_id TEXT UNIQUE,request_id TEXT UNIQUE,payload_hash TEXT,organisation_name TEXT,website TEXT,contact_name TEXT,email TEXT,contact_method TEXT,phone TEXT,status TEXT,source TEXT,created_at TEXT,updated_at TEXT)');
    }
    function prepare($sql, ...$values) { $i = 0; return preg_replace_callback('/%[sd]/', function ($m) use (&$i, $values) { $v = $values[$i++]; return $m[0] === '%d' ? (int)$v : $this->db->quote($v); }, $sql); }
    function query($sql) { if ($this->fail) return false; $sql = str_replace('ON DUPLICATE KEY UPDATE attempts=attempts+1', 'ON CONFLICT(bucket) DO UPDATE SET attempts=attempts+1', $sql); if (str_starts_with($sql, 'DELETE FROM test_pps_applications')) $sql = 'DELETE FROM test_pps_applications WHERE id IN (SELECT id FROM test_pps_applications ' . substr($sql, strlen('DELETE FROM test_pps_applications ')) . ')'; return $this->db->exec($sql); }
    function get_var($sql) { return $this->db->query($sql)->fetchColumn(); }
    function get_row($sql) { return $this->db->query($sql)->fetch(PDO::FETCH_OBJ); }
    function get_results($sql, $format = null) { return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
    function suppress_errors(...$args) { return false; }
    function insert($table, $data) {
        if ($this->fail || $this->failInsert) return false;
        try { $stmt = $this->db->prepare('INSERT INTO ' . $table . ' (' . implode(',', array_keys($data)) . ') VALUES (' . implode(',', array_fill(0,count($data),'?')) . ')'); $stmt->execute(array_values($data)); return $this->race ? false : 1; } catch (PDOException $e) { return false; }
    }
}
require __DIR__ . '/../puzzlepath-sponsorship/core.php';
require __DIR__ . '/../puzzlepath-sponsorship/submission.php';
require __DIR__ . '/../puzzlepath-sponsorship/privacy.php';
if (defined('PPS_TEST_LIBRARY')) return;
$path = tempnam(sys_get_temp_dir(), 'pps-test-'); $wpdb = new Database($path);
$checks = 0;
function check($condition, $label) { global $checks; if (!$condition) throw new Exception('FAILED: ' . $label); $checks++; }
function submit(array $input, $request = null, int $age = 5): Response {
    $request ??= bin2hex(random_bytes(16)); $issued = time() - $age;
    $_POST = array_merge($input, ['request_id' => $request, 'issued' => $issued, 'token' => pps_token($request, $issued, $_COOKIE['pps_session']), 'company_fax' => $input['company_fax'] ?? '']);
    try { pps_submit(); } catch (Response $r) { return $r; } throw new Exception('No response');
}
$_SERVER = ['REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'https://example.test', 'REMOTE_ADDR' => '192.0.2.1'];
$_COOKIE['pps_session'] = str_repeat('a', 64);
$valid = ['organisation_name' => 'Test Community', 'website' => 'example.org.au', 'contact_name' => 'Test Person', 'email' => 'test@example.org', 'contact_method' => 'email', 'phone' => ''];
try {
    [$normal, $errors] = pps_validate($valid); check(!$errors && $normal['website'] === 'https://example.org.au', 'website normalisation');
    foreach (['0412 345 678', '(07) 5555 1234', '+61 412 345 678', '+61 (0)7 5555 1234', '1300 123 456', '13 12 34'] as $phone) check(!pps_validate(array_merge($valid, ['contact_method' => 'phone','phone' => $phone]))[1], 'valid Australian phone: ' . $phone);
    foreach (['organisation_name' => '', 'contact_name' => '', 'email' => 'bad-email', 'website' => 'javascript:alert(1)', 'contact_method' => 'sms'] as $key => $bad) check(isset(pps_validate(array_merge($valid, [$key => $bad]))[1][$key]), 'reject invalid ' . $key);
    check(isset(pps_validate(array_merge($valid, ['contact_method' => 'phone', 'phone' => '123']))[1]['phone']), 'invalid phone');
    check(pps_validate(array_merge($valid, ['phone' => '+1 555 555 5555']))[0]['phone'] === '', 'email ignores phone');
    check(isset(pps_validate(array_merge($valid, ['email' => ['attack']]))[1]['email']), 'array input rejected');
    check(pps_validate(array_merge($valid, ['organisation_name' => '<b>Community</b>']))[0]['organisation_name'] === 'Community', 'strip markup');
    $id = bin2hex(random_bytes(16)); $first = submit($valid, $id);
    check($first->status === 200, 'email submission saved');
    $retry = submit($valid, $id); check($first->body === $retry->body, 'idempotent retry returns same reference');
    check((int)$wpdb->get_var('SELECT count(*) FROM test_pps_applications') === 1, 'retry creates no duplicate');
    check(submit(array_merge($valid, ['organisation_name' => 'Changed']), $id)->status === 409, 'changed retry rejected');
    $row = $wpdb->get_row('SELECT * FROM test_pps_applications'); check($row->status === 'new' && $row->source === 'website_sponsorship_form' && !empty($row->created_at), 'record metadata');
    check(array_keys($first->body['data']) === ['reference_id'], 'response exposes no contact details');
    $phone = submit(array_merge($valid, ['contact_method' => 'phone', 'phone' => '0412 345 678'])); check($phone->status === 200, 'phone submission saved');
    $wpdb = new Database($path); check((int)$wpdb->get_var('SELECT count(*) FROM test_pps_applications') === 2, 'records survive connection restart');
    check(submit(array_merge($valid, ['email' => 'invalid']))->status === 422, 'server field errors');
    check(submit(array_merge($valid, ['company_fax' => 'spam']))->status === 400, 'honeypot');
    check(submit($valid, null, 0)->status === 400, 'minimum fill time');
    check(submit($valid, null, 7201)->status === 403, 'expired session');
    $_SERVER['HTTP_ORIGIN'] = 'https://evil.test'; check(submit($valid)->status === 403, 'cross-origin rejected'); $_SERVER['HTTP_ORIGIN'] = 'https://example.test';
    $_COOKIE['pps_session'] = 'invalid'; check(submit($valid)->status === 403, 'bad cookie rejected'); $_COOKIE['pps_session'] = str_repeat('a',64);
    $GLOBALS['ssl'] = false; check(submit($valid)->status === 403, 'HTTP rejected'); $GLOBALS['ssl'] = true;
    $GLOBALS['privacy'] = ''; check(submit($valid)->status === 503, 'missing policy disables submission'); unset($GLOBALS['privacy']);
    $wpdb->fail = true; check(submit($valid)->status === 503, 'database outage does not report success'); $wpdb->fail = false;
    $_SERVER['REMOTE_ADDR'] = '192.0.2.2'; $wpdb->failInsert = true; check(submit($valid)->status === 503, 'application insert failure does not report success'); $wpdb->failInsert = false; $_SERVER['REMOTE_ADDR'] = '192.0.2.1';
    $wpdb->race = true; check(submit($valid)->status === 200, 'concurrent insertion recovery'); $wpdb->race = false;
    submit($valid); submit($valid); check(submit($valid)->status === 429, 'rate limit');
    check(!pps_public_organisations([['name' => 'Private', 'sponsorship_status' => 'confirmed', 'public_display_enabled' => true]]), 'permission required');
    check(count(pps_public_organisations([['name' => 'Approved', 'sponsorship_status' => 'confirmed', 'public_display_enabled' => true, 'publication_authorised_at' => '2026-10-25']])) === 1, 'approved recipient displayed');
    $export = pps_privacy_export('test@example.org'); check(count($export['data']) === 5, 'privacy export');
    $erase = pps_privacy_erase('test@example.org'); check($erase['items_removed'] && $erase['done'], 'privacy erase');
    check((int)$wpdb->get_var('SELECT count(*) FROM test_pps_applications') === 0, 'privacy deletion persisted');
    echo "PASS: $checks validation, security, persistence and privacy checks (SQLite WordPress adapter).\n";
} finally { unset($wpdb); @unlink($path); }
