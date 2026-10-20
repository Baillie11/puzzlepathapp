<?php
require dirname(__DIR__) . '/portal-core.php';
$checks = 0;
function check($ok, $message) { global $checks; $checks++; if (!$ok) throw new RuntimeException($message); }
$zone = new DateTimeZone('Australia/Brisbane');
$now = (new DateTimeImmutable('2026-10-20 12:00:00', $zone))->getTimestamp();
$base = ['code' => ' phone-25 ', 'percent' => '25', 'uses' => '1', 'expiry' => '2026-10-20'];
$data = ppdp_validate($base, $zone, $now);
check($data['code'] === 'PHONE-25', 'Normalises code');
check($data['expires_at'] === '2026-10-20 23:59:59', 'Expiry includes the selected day');
check($data['times_used'] === 0 && $data['max_uses'] === 1, 'Starts with no redemptions');
foreach ([['code','ab'], ['code','<script>'], ['code',str_repeat('A',51)], ['percent','0'], ['percent','101'], ['percent','25.5'], ['uses','-1'], ['uses','1000001'], ['expiry','2026-02-30'], ['expiry','2026-10-19']] as [$field,$value]) {
    $bad = $base; $bad[$field] = $value;
    try { ppdp_validate($bad, $zone, $now); throw new RuntimeException('Accepted invalid ' . $field); }
    catch (InvalidArgumentException $e) { $checks++; }
}
$free = ppdp_validate(array_merge($base, ['percent'=>'100','uses'=>'0','expiry'=>'']), $zone, $now);
check($free['discount_percent']===100 && $free['max_uses']===0 && $free['expires_at']===null, 'Free, unlimited and no-expiry supported');
$random = str_repeat('a',64); $key = 'test-secret';
$token = ppdp_token($now+86400,$random,$key);
check(ppdp_valid_token($token,$key,$now), 'Valid session accepted');
check(!ppdp_valid_token($token,'rotated-key',$now), 'Password rotation invalidates sessions');
check(!ppdp_valid_token($token,$key,$now+86400), 'Expired sessions rejected');
check(!ppdp_valid_token(substr($token,0,-1).'x',$key,$now), 'Tampered session rejected');
check(!ppdp_valid_token(ppdp_token($now+31*86400,$random,$key),$key,$now), 'Excessive lifetime rejected');
check(!ppdp_valid_token(['bad'],$key,$now), 'Malformed cookie rejected');
echo "Passed $checks checks.\n";
