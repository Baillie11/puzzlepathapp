<?php
// Pure helpers, also exercised by tests without WordPress.
function ppdp_validate(array $input, DateTimeZone $timezone, $now = null) {
    $code = strtoupper(trim((string)($input['code'] ?? '')));
    if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{2,49}$/D', $code)) {
        throw new InvalidArgumentException('Use 3–50 letters, numbers, dashes or underscores for the code.');
    }
    $percent = filter_var($input['percent'] ?? '', FILTER_VALIDATE_INT);
    $uses = filter_var($input['uses'] ?? '', FILTER_VALIDATE_INT);
    if ($percent === false || $percent < 1 || $percent > 100) throw new InvalidArgumentException('Choose a discount from 1% to 100%.');
    if ($uses === false || $uses < 0 || $uses > 1000000) throw new InvalidArgumentException('Enter a usage limit between 0 and 1,000,000.');
    $expiry = null;
    if (!empty($input['expiry'])) {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $input['expiry'], $timezone);
        if (!$date || $date->format('Y-m-d') !== $input['expiry']) throw new InvalidArgumentException('Choose a valid expiry date.');
        $date = $date->setTime(23, 59, 59);
        if ($date->getTimestamp() <= ($now ?? time())) throw new InvalidArgumentException('Expiry must be today or later.');
        $expiry = $date->format('Y-m-d H:i:s');
    }
    return ['code' => $code, 'discount_percent' => $percent, 'max_uses' => $uses, 'times_used' => 0, 'expires_at' => $expiry];
}

function ppdp_token($expires, $random, $key) {
    $body = $expires . '.' . $random;
    return $body . '.' . hash_hmac('sha256', $body, $key);
}

function ppdp_valid_token($token, $key, $now = null) {
    if (!is_string($token) || !preg_match('/^(\d{10})\.([a-f0-9]{64})\.([a-f0-9]{64})$/D', $token, $parts)) return false;
    $now = $now ?? time();
    if ((int)$parts[1] <= $now || (int)$parts[1] > $now + 30 * 86400) return false;
    return hash_equals(ppdp_token($parts[1], $parts[2], $key), $token);
}
