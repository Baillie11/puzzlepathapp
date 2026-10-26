<?php
// Pure validation shared by the WordPress endpoint and the regression tests.
function pps_validate(array $input): array {
    $data = []; $errors = [];
    foreach (['organisation_name' => 160, 'website' => 500, 'contact_name' => 120, 'email' => 254, 'contact_method' => 10, 'phone' => 40] as $field => $limit) {
        if (!is_string($input[$field] ?? '')) { $errors[$field] = 'Please enter a valid value.'; $data[$field] = ''; continue; }
        $value = trim(strip_tags($input[$field] ?? ''));
        if (strlen($value) > $limit || preg_match('/[\x00-\x1F\x7F]/', $value)) $errors[$field] = 'Please check this value and its length.';
        $data[$field] = $value;
    }
    foreach (['organisation_name', 'contact_name'] as $field) if ($data[$field] === '') $errors[$field] = 'Please complete this field.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
    if ($data['website'] !== '') {
        if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $data['website'])) $data['website'] = 'https://' . $data['website'];
        $parts = parse_url($data['website']);
        if (!$parts || !filter_var($data['website'], FILTER_VALIDATE_URL) || !in_array(strtolower($parts['scheme'] ?? ''), ['https', 'http'], true) || !str_contains($parts['host'] ?? '', '.') || isset($parts['user']) || isset($parts['pass'])) $errors['website'] = 'Please enter a website such as example.org.au.';
    }
    if (!in_array($data['contact_method'], ['email', 'phone'], true)) $errors['contact_method'] = 'Please choose email or phone call.';
    if ($data['contact_method'] === 'phone') {
        $phone = preg_replace('/[\s().-]/', '', $data['phone']);
        if (str_starts_with($phone, '+610')) $phone = '+61' . substr($phone, 4);
        if (preg_match('/^0[23478][0-9]{8}$/', $phone)) $phone = '+61' . substr($phone, 1);
        if (!preg_match('/^(\+61[23478][0-9]{8}|1[38]00[0-9]{6}|13[0-9]{4})$/', $phone)) $errors['phone'] = 'Please enter a valid Australian phone number.';
        $data['phone'] = $phone;
    } else { $data['phone'] = ''; unset($errors['phone']); }
    return [$data, $errors];
}

function pps_public_organisations(array $organisations): array {
    $approved = array_filter($organisations, fn($o) => ($o['sponsorship_status'] ?? '') === 'confirmed' && ($o['public_display_enabled'] ?? false) === true && !empty($o['publication_authorised_at']));
    usort($approved, fn($a, $b) => ($a['display_order'] ?? 0) <=> ($b['display_order'] ?? 0));
    return $approved;
}
