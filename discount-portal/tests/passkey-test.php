<?php
define('ABSPATH', __DIR__);
$options = []; $transients = [];
function add_action(...$args) {}
function home_url($path) { return 'https://puzzlepath.com.au' . $path; }
function wp_parse_url(...$args) { return parse_url(...$args); }
function get_option($key) { global $options; return $options[$key] ?? false; }
function add_option($key, $value, ...$args) { global $options; if (isset($options[$key])) return false; $options[$key] = $value; return true; }
function update_option($key, $value, ...$args) { global $options; $options[$key] = $value; return true; }
function get_transient($key) { global $transients; return $transients[$key] ?? false; }
function set_transient($key, $value, $ttl) { global $transients; $transients[$key] = $value; }
function wp_schedule_single_event(...$args) {}
function wp_cache_delete(...$args) {}
function maybe_serialize($value) { return serialize($value); }
class TestDatabase {
    public $options = 'wp_options';
    function prepare($sql, ...$args) { return [$sql, $args]; }
    function esc_like($value) { return $value; }
    function get_col($query) { global $options; return array_values(array_filter(array_keys($options), fn($name) => strpos($name, 'ppdp_credential_') === 0)); }
    function query($query) {
        global $options; [$sql, $args] = $query;
        if (isset($options[$args[0]]) && serialize($options[$args[0]]) === $args[1]) { unset($options[$args[0]]); return 1; }
        return 0;
    }
}
$wpdb = new TestDatabase();
require dirname(__DIR__) . '/portal-passkeys.php';
$checks = 0;
function check($value, $message) { global $checks; $checks++; if (!$value) throw new RuntimeException($message); }
function rejects($fn, $message) { global $checks; try { $fn(); } catch (Throwable $e) { $checks++; return; } throw new RuntimeException('Accepted: ' . $message); }
function cborHead($major, $length) { return $length < 24 ? chr(($major << 5) | $length) : ($length < 256 ? chr(($major << 5) | 24) . chr($length) : chr(($major << 5) | 25) . pack('n', $length)); }
function cborBytes($value) { return cborHead(2, strlen($value)) . $value; }
function cborText($value) { return cborHead(3, strlen($value)) . $value; }
$key = 'integration-test-key'; $binding = 'test-browser-session'; $password = 'test-password-for-local-tests';
$hash = password_hash($password, PASSWORD_DEFAULT);
$_SERVER['REMOTE_ADDR'] = 'test';
$pair = openssl_pkey_new(['private_key_type'=>OPENSSL_KEYTYPE_EC, 'curve_name'=>'prime256v1']);
if (!$pair) throw new RuntimeException('Test OpenSSL key generation failed.');
$details = openssl_pkey_get_details($pair);
// An authenticator's ES256 COSE public key, encoded as a five-entry CBOR map.
$cose = "\xa5\x01\x02\x03\x26\x20\x01\x21" . cborBytes($details['ec']['x']) . "\x22" . cborBytes($details['ec']['y']);
$id = random_bytes(32);
function clientData($type, $challenge, $origin = 'https://puzzlepath.com.au', $crossOrigin = false) { return ppdp_b64(json_encode(compact('type', 'challenge', 'origin', 'crossOrigin'))); }
function attestation($id, $cose, $flags = 0x45, $rp = 'puzzlepath.com.au') {
    $auth = hash('sha256', $rp, true) . chr($flags) . pack('N', 0) . str_repeat("\0",16) . pack('n',strlen($id)) . $id . $cose;
    return ppdp_b64("\xa3" . cborText('fmt') . cborText('none') . cborText('attStmt') . "\xa0" . cborText('authData') . cborBytes($auth));
}
function registerInput($start, $id, $cose, $flags = 0x45) {
    return ['action'=>'passkey_register_finish','request'=>$start['request'],'id'=>ppdp_b64($id),'clientDataJSON'=>clientData('webauthn.create', ppdp_b64($start['options']->publicKey->challenge->getBinaryString())), 'attestationObject'=>attestation($id,$cose,$flags)];
}
rejects(fn()=>ppdp_passkey_request(['action'=>'passkey_register_options','password'=>$password],false,$binding,$key,$hash),'anonymous registration');
rejects(fn()=>ppdp_passkey_request(['action'=>'passkey_register_options','password'=>'wrong'],true,$binding,$key,$hash),'registration without password proof');
$start = ppdp_passkey_request(['action'=>'passkey_register_options','password'=>$password],true,$binding,$key,$hash);
check($start['options']->publicKey->authenticatorSelection->userVerification === 'required', 'Registration requires user verification');
check($start['options']->publicKey->authenticatorSelection->residentKey === 'required', 'Discoverable key required');
$registration = registerInput($start,$id,$cose);
check(ppdp_passkey_request($registration,true,$binding,$key,$hash)['registered'], 'Valid registration');
rejects(fn()=>ppdp_passkey_request($registration,true,$binding,$key,$hash),'registration replay');
$badStart = ppdp_passkey_request(['action'=>'passkey_register_options','password'=>$password],true,$binding,$key,$hash);
rejects(fn()=>ppdp_passkey_request(registerInput($badStart,random_bytes(32),$cose,0x41),true,$binding,$key,$hash),'registration without biometric or PIN verification');
function loginInput($start, $id, $pair, $key, $flags = 0x05, $rp = 'puzzlepath.com.au', $origin = 'https://puzzlepath.com.au') {
    $client = clientData('webauthn.get', ppdp_b64($start['options']->publicKey->challenge->getBinaryString()), $origin);
    $auth = hash('sha256',$rp,true) . chr($flags) . pack('N',0);
    openssl_sign($auth . hash('sha256',ppdp_unb64($client),true),$signature,$pair,OPENSSL_ALGO_SHA256);
    return ['action'=>'passkey_login_finish','request'=>$start['request'],'id'=>ppdp_b64($id),'clientDataJSON'=>$client,'authenticatorData'=>ppdp_b64($auth),'signature'=>ppdp_b64($signature),'userHandle'=>ppdp_b64(ppdp_passkey_owner($key))];
}
$get = fn()=>ppdp_passkey_request(['action'=>'passkey_login_options'],false,$binding,$key,$hash);
$start = $get();
check($start['options']->publicKey->userVerification === 'required', 'Login requires user verification');
check(!isset($start['options']->publicKey->allowCredentials), 'No public credential enumeration');
$login = loginInput($start,$id,$pair,$key);
check(ppdp_passkey_request($login,false,$binding,$key,$hash)['authenticated'], 'Valid signed assertion authenticates');
rejects(fn()=>ppdp_passkey_request($login,false,$binding,$key,$hash),'login replay');
foreach ([['flags'=>1],['rp'=>'attacker.test'],['origin'=>'https://sub.puzzlepath.com.au']] as $case) {
    $start = $get(); $bad = loginInput($start,$id,$pair,$key,$case['flags']??5,$case['rp']??'puzzlepath.com.au',$case['origin']??'https://puzzlepath.com.au');
    rejects(fn()=>ppdp_passkey_request($bad,false,$binding,$key,$hash),'incorrect UV, RP or origin');
}
$start = $get(); $bad = loginInput($start,$id,$pair,$key); $bad['signature'] = ppdp_b64(random_bytes(70));
rejects(fn()=>ppdp_passkey_request($bad,false,$binding,$key,$hash),'invalid signature');
$start = $get(); $bad = loginInput($start,$id,$pair,$key); $bad['userHandle'] = ppdp_b64(random_bytes(32));
rejects(fn()=>ppdp_passkey_request($bad,false,$binding,$key,$hash),'incorrect owner');
$start = $get(); $bad = loginInput($start,$id,$pair,$key);
rejects(fn()=>ppdp_passkey_request($bad,false,'different-browser',$key,$hash),'different browser binding');
$start = $get(); $bad = loginInput($start,$id,$pair,$key); $options['ppdp_challenge_'.$start['request']]['expires'] = time()-1;
rejects(fn()=>ppdp_passkey_request($bad,false,$binding,$key,$hash),'expired request');
$transients=[];
$newKey='rotated-key'; $start=ppdp_passkey_request(['action'=>'passkey_login_options'],false,$binding,$newKey,$hash);
$bad=loginInput($start,$id,$pair,$newKey);
rejects(fn()=>ppdp_passkey_request($bad,false,$binding,$newKey,$hash),'password rotation revokes passkeys');
rejects(fn()=>ppdp_client_data(clientData('webauthn.get','abc','https://puzzlepath.com.au',true),ppdp_origin()),'cross-origin iframe');
rejects(fn()=>ppdp_unb64('not+base64/url='),'malformed encoded binary');
$transients=['ppdp_passkey_'.hash('sha256','test')=>15];
rejects(fn()=>$get(),'rate limit');
echo "Passed $checks WebAuthn integration checks (real ES256 keys and signatures).\n";
