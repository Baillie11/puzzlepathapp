<?php
// Local adapter tests: no network, real mail, database or WordPress account involved.
define('ABSPATH', __DIR__);
define('MINUTE_IN_SECONDS', 60);
function add_action(...$args) {}
function add_filter(...$args) {}
function add_shortcode(...$args) {}
function register_activation_hook(...$args) {}
function wp_unslash($s) { return stripslashes($s); }
function sanitize_text_field($s) { return trim(preg_replace('/[\r\n\t]+/', ' ', strip_tags($s))); }
function sanitize_textarea_field($s) { return strip_tags($s); }
function esc_html($s) { return htmlspecialchars($s); }
function wp_verify_nonce($nonce, $action) { return $nonce === 'valid-nonce'; }
function is_email($s) { return filter_var($s, FILTER_VALIDATE_EMAIL); }
function wp_salt($s) { return 'local-test-only'; }
function get_transient($key) { return $GLOBALS['transients'][$key] ?? false; }
function set_transient($key, $value, $ttl) { $GLOBALS['transients'][$key] = $value; }
function get_option($key) { return $key === 'pp_partner_page_id' ? 123 : false; }
function get_permalink($id) { return 'https://example.test/partnership/'; }
function home_url($s) { return 'https://example.test'.$s; }
function add_query_arg($key,$value,$url) { return $url.'?'.$key.'='.$value; }
function wp_die($message,$title,$args) { throw new RuntimeException($message,$args['response']); }
function wp_safe_redirect($url) { throw new RuntimeException($url,302); }
function wp_mail($to,$subject,$body,$headers) { $GLOBALS['mail'][] = compact('to','subject','body','headers'); return $GLOBALS['mail_result']; }
require __DIR__.'/../puzzlepath-partnership/puzzlepath-partnership.php';
$base = ['pp_partner_nonce'=>'valid-nonce','company_fax'=>'','business'=>'Test Business','location'=>'Gold Coast','contact_name'=>'Test Person','email'=>'preview@example.com','contact_method'=>'Email','business_type'=>'Retail / Shopping','consent'=>'yes','involvement'=>['Have a clue or puzzle based on my business'],'notes'=>'Synthetic enquiry only'];
$cases = [
    ['valid enquiry',[],302],
    ['missing consent',['consent'=>''],400],
    ['invalid email',['email'=>'not-an-email'],400],
    ['expired nonce',['pp_partner_nonce'=>'expired'],403],
    ['honeypot',['company_fax'=>'spam'],400],
    ['phone without number',['contact_method'=>'Phone'],400],
    ['phone with number',['contact_method'=>'Phone','phone'=>'0000000000'],302],
    ['unexpected involvement',['involvement'=>['unexpected']],400],
    ['array instead of name',['contact_name'=>['test']],400],
    ['invalid business type',['business_type'=>'unexpected'],400],
    ['invalid offer',['offer'=>'unexpected'],400],
    ['message too long',['notes'=>str_repeat('a',16001)],400],
];
foreach ($cases as [$name,$overrides,$expected]) {
    $_POST = array_replace($base,$overrides);
    $_SERVER = ['REQUEST_METHOD'=>'POST','REMOTE_ADDR'=>'127.0.0.1'];
    $GLOBALS['transients']=[]; $GLOBALS['mail']=[]; $GLOBALS['mail_result']=true;
    try { pp_partner_submit(); throw new Exception('No result'); }
    catch (RuntimeException $e) { if ($e->getCode() !== $expected) { throw new Exception($name.': unexpected result '.$e->getCode()); } }
    if ($expected !== 302 && count($GLOBALS['mail'])) { throw new Exception($name.': rejected input sent mail'); }
    if ($expected === 302 && ($GLOBALS['mail'][0]['to'] !== 'info@puzzlepath.com.au' || !str_contains($GLOBALS['mail'][0]['body'],'Test Business'))) { throw new Exception('Mail destination or content incorrect'); }
    echo "PASS $name\n";
}
$_POST=$base; $GLOBALS['transients']=[]; $GLOBALS['mail']=[]; $GLOBALS['mail_result']=false;
try { pp_partner_submit(); } catch (RuntimeException $e) { if ($e->getCode() !== 503) { throw $e; } }
if ($GLOBALS['transients']) { throw new Exception('Failed mail must permit retry'); }
echo "PASS mail failure permits retry\n";
$GLOBALS['mail_result']=true;
try { pp_partner_submit(); } catch (RuntimeException $e) { if ($e->getCode() !== 302) { throw $e; } }
$mail_count=count($GLOBALS['mail']);
try { pp_partner_submit(); } catch (RuntimeException $e) { if ($e->getCode() !== 429) { throw $e; } }
if (count($GLOBALS['mail']) !== $mail_count) { throw new Exception('Duplicate enquiry sent mail'); }
echo "PASS repeated submission rate limit\n";
