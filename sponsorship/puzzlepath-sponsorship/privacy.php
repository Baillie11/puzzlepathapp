<?php
defined('ABSPATH') || exit;
// Integrate with WordPress's administrator-only, email-verified privacy tools.
add_filter('wp_privacy_personal_data_exporters', function ($exporters) {
    $exporters['puzzlepath-sponsorship'] = ['exporter_friendly_name' => 'Puzzle Path sponsorship enquiries', 'callback' => 'pps_privacy_export']; return $exporters;
});
function pps_privacy_export($email, $page = 1): array {
    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pps_applications WHERE email=%s ORDER BY id LIMIT 100 OFFSET %d", $email, (max(1, (int)$page) - 1) * 100), ARRAY_A);
    $export = [];
    foreach ($rows as $row) {
        $data = [];
        foreach (['reference_id', 'organisation_name', 'website', 'contact_name', 'email', 'contact_method', 'phone', 'status', 'source', 'created_at'] as $field) $data[] = ['name' => ucwords(str_replace('_', ' ', $field)), 'value' => $row[$field]];
        $export[] = ['group_id' => 'puzzlepath-sponsorship', 'group_label' => 'Sponsorship enquiries', 'item_id' => 'pps-' . $row['reference_id'], 'data' => $data];
    }
    return ['data' => $export, 'done' => count($rows) < 100];
}
add_filter('wp_privacy_personal_data_erasers', function ($erasers) {
    $erasers['puzzlepath-sponsorship'] = ['eraser_friendly_name' => 'Puzzle Path sponsorship enquiries', 'callback' => 'pps_privacy_erase']; return $erasers;
});
function pps_privacy_erase($email, $page = 1): array {
    global $wpdb;
    $result = $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}pps_applications WHERE email=%s LIMIT 100", $email));
    return ['items_removed' => $result > 0, 'items_retained' => $result === false, 'messages' => $result === false ? ['Enquiries could not be erased. Please retry.'] : [], 'done' => $result !== false && $result < 100];
}
add_action('admin_init', function () {
    if (function_exists('wp_add_privacy_policy_content')) wp_add_privacy_policy_content('Puzzle Path Community Sponsorship', '<p>Sponsorship enquiries collect organisation name, optional website, contact name, email address and, when requested, an Australian phone number. Puzzle Path uses these details to assess and respond to sponsorship enquiries. They are stored privately in the website database and do not subscribe applicants to marketing. Short-lived pseudonymous rate-limit records help prevent spam. Administrators should define and apply an enquiry retention period appropriate to their operations and include this processing in the published Privacy Policy.</p>');
});
