<?php
/**
 * Plugin Name: Puzzle Path Partnership
 * Description: Koko partnership page and enquiry form. Shortcode: [puzzlepath_partnership]
 * Version: 1.1.0
 */
if (!defined('ABSPATH')) { exit; }

function pp_partner_is_page() {
    return is_singular() && has_shortcode(get_post()->post_content ?? '', 'puzzlepath_partnership');
}
add_filter('body_class', function ($classes) { if (pp_partner_is_page()) { $classes[] = 'pp-partner-page'; } return $classes; });
add_action('wp_enqueue_scripts', function () {
    if (!pp_partner_is_page()) { return; }
    wp_enqueue_style('pp-partnership', plugin_dir_url(__FILE__) . 'assets/partnership.css', [], '1.1.0');
    wp_enqueue_script('pp-partnership', plugin_dir_url(__FILE__) . 'assets/partnership.js', [], '1.1.0', true);
});
add_shortcode('puzzlepath_partnership', function () { ob_start(); include __DIR__ . '/view.php'; return ob_get_clean(); });

// Publication is explicitly requested. Preserve unrelated content at the same slug.
register_activation_hook(__FILE__, function () {
    $page = get_page_by_path('partnership');
    if ($page && !has_shortcode($page->post_content, 'puzzlepath_partnership')) { return; }
    $id = $page ? wp_update_post(['ID'=>$page->ID, 'post_status'=>'publish'], true) : wp_insert_post(['post_type'=>'page', 'post_status'=>'publish', 'post_title'=>'Partner with Puzzle Path', 'post_name'=>'partnership', 'post_content'=>'[puzzlepath_partnership]'], true);
    if ($id && !is_wp_error($id)) {
        update_post_meta($id, 'site-sidebar-layout', 'no-sidebar');
        update_post_meta($id, 'site-content-layout', 'page-builder');
        update_post_meta($id, 'ast-title-bar-display', 'disabled');
        update_post_meta($id, 'theme-transparent-header-meta', 'disabled');
        update_option('pp_partner_page_id', $id, false);
    }
});
// Keep the theme's own desktop and mobile menu walkers and avoid duplicate links.
add_filter('wp_nav_menu_objects', function ($items, $args) {
    $id = (int)get_option('pp_partner_page_id');
    if (!$id || get_post_status($id) !== 'publish') { return $items; }
    $url = get_permalink($id);
    $eligible = in_array($args->theme_location ?? '', ['primary', 'mobile_menu'], true);
    foreach ($items as $item) {
        if (untrailingslashit($item->url) === untrailingslashit($url)) { return $items; }
        if (preg_match('~/(about|contact)/?$~', $item->url)) { $eligible = true; }
    }
    if (!$eligible) { return $items; }
    $item = new WP_Post((object)['ID'=>-92002, 'post_title'=>'Partnership', 'post_type'=>'nav_menu_item']);
    foreach (['title'=>'Partnership','url'=>$url,'menu_item_parent'=>0,'db_id'=>0,'object_id'=>$id,'object'=>'page','type'=>'post_type','type_label'=>'Page','target'=>'','attr_title'=>'','description'=>'','xfn'=>'','classes'=>['menu-item','menu-item-type-post_type','menu-item-object-page'],'current'=>is_page($id),'current_item_ancestor'=>false,'current_item_parent'=>false] as $key=>$value) { $item->$key = $value; }
    $items[] = $item;
    return $items;
}, 10, 2);
// A cached form nonce can expire before a visitor opens the page.
add_action('template_redirect', function () {
    if (!pp_partner_is_page()) { return; }
    if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
    nocache_headers();
    do_action('litespeed_control_set_nocache', 'Partnership enquiry form nonce');
});
add_action('admin_notices', function () {
    if (current_user_can('manage_options') && !get_option('pp_partner_page_id')) {
        echo '<div class="notice notice-error"><p>Puzzle Path Partnership: the partnership page could not be created. Check whether /partnership is already in use before reactivating this plugin.</p></div>';
    }
});

function pp_partner_clean($key, $max = 500) {
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) { return ''; }
    return substr(sanitize_text_field(wp_unslash($value)), 0, $max);
}
function pp_partner_fail($message, $code = 400) {
    wp_die(esc_html($message), 'Partnership enquiry', ['response'=>$code, 'back_link'=>true]);
}
function pp_partner_submit() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { pp_partner_fail('Please submit the partnership form.', 405); }
    if (!wp_verify_nonce(pp_partner_clean('pp_partner_nonce'), 'pp_partner_enquiry')) { pp_partner_fail('This form has expired. Please reload the partnership page and try again.', 403); }
    if (pp_partner_clean('company_fax') !== '') { pp_partner_fail('Your enquiry could not be submitted. Please contact info@puzzlepath.com.au.', 400); }
    $data = [];
    foreach (['business'=>200, 'location'=>500, 'contact_name'=>200, 'phone'=>60, 'website'=>500, 'business_type'=>100, 'contact_method'=>20, 'offer'=>100] as $key=>$max) { $data[$key] = pp_partner_clean($key, $max); }
    $email = pp_partner_clean('email', 254);
    if (!$data['business'] || !$data['location'] || !$data['contact_name'] || !is_email($email) || pp_partner_clean('consent') !== 'yes') { pp_partner_fail('Please complete the required business and contact details, enter a valid email address, and agree to be contacted.'); }
    if (!in_array($data['contact_method'], ['Email','Phone'], true)) { pp_partner_fail('Please choose email or phone as your preferred contact method.'); }
    if ($data['contact_method'] === 'Phone' && !$data['phone']) { pp_partner_fail('Please enter a phone number when choosing phone contact.'); }
    $types = ['','Retail / Shopping','Cafe / Restaurant / Food','Tourism / Attractions / Entertainment','Community / Not-for-profit','Other'];
    $offers = ['', 'Yes, I’d be interested', 'Maybe, let’s discuss', 'No, not at this stage'];
    if (!in_array($data['business_type'], $types, true) || !in_array($data['offer'], $offers, true)) { pp_partner_fail('Please select one of the available business types and offer options.'); }
    $allowed = ['Feature my business as a scavenger hunt checkpoint', 'Have a clue or puzzle based on my business', 'Provide an optional special offer or reward', 'Explore a customised scavenger hunt', 'I’m open to suggestions'];
    $interests = $_POST['involvement'] ?? [];
    if (!is_array($interests) || count($interests) > count($allowed)) { pp_partner_fail('Please select valid involvement options.'); }
    $selected = [];
    foreach ($interests as $item) {
        if (!is_string($item) || !in_array(wp_unslash($item), $allowed, true)) { pp_partner_fail('Please select valid involvement options.'); }
        $selected[] = wp_unslash($item);
    }
    $notes = $_POST['notes'] ?? '';
    if (!is_string($notes) || strlen($notes) > 16000) { pp_partner_fail('Please keep your message to 4,000 characters.'); }
    $notes = sanitize_textarea_field(wp_unslash($notes));
    // Store only short-lived hashed identifiers, not enquiry content.
    $rate_key = 'pp_partner_' . hash_hmac('sha256', strtolower($email), wp_salt('nonce'));
    $ip_key = 'pp_partner_ip_' . hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown', wp_salt('nonce'));
    if (get_transient($rate_key) || (int)get_transient($ip_key) >= 5) { pp_partner_fail('Please wait a few minutes before sending another enquiry, or email info@puzzlepath.com.au.', 429); }
    $body = "New Puzzle Path partnership expression of interest\n\n";
    $labels = ['business'=>'Business / Organisation', 'location'=>'Address / Location', 'website'=>'Website / Social Media', 'business_type'=>'Business Type', 'contact_name'=>'Contact Person', 'phone'=>'Phone', 'contact_method'=>'Preferred Contact Method', 'offer'=>'Optional Offer / Reward'];
    foreach ($labels as $key=>$label) { $body .= $label . ': ' . ($data[$key] ?: 'Not provided') . "\n"; }
    $body .= "Email: $email\n\nInvolvement interests:\n" . ($selected ? implode("\n", $selected) : 'Not selected') . "\n\nAdditional information:\n" . ($notes ?: 'Not provided') . "\n\nConsent: Agreed to be contacted about this partnership enquiry.\nRegistration is an expression of interest, not guaranteed inclusion.\n";
    $sent = wp_mail('info@puzzlepath.com.au', 'Puzzle Path partnership enquiry', $body, ['Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $email]);
    if (!$sent) { pp_partner_fail('Your enquiry could not be sent. Please go back to retry, or email info@puzzlepath.com.au.', 503); }
    set_transient($rate_key, 1, 2 * MINUTE_IN_SECONDS);
    set_transient($ip_key, (int)get_transient($ip_key) + 1, 10 * MINUTE_IN_SECONDS);
    $id = (int)get_option('pp_partner_page_id');
    $url = $id ? get_permalink($id) : home_url('/partnership/');
    wp_safe_redirect(add_query_arg('partner_status', 'sent', $url) . '#partner-register');
    exit;
}
add_action('admin_post_nopriv_pp_partner_enquiry', 'pp_partner_submit');
add_action('admin_post_pp_partner_enquiry', 'pp_partner_submit');
