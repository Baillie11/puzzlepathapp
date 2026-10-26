<?php
/**
 * Plugin Name: Puzzle Path Community Sponsorship
 * Description: Community sponsorship page and private, durable enquiry recording.
 * Version: 1.0.1
 * Requires PHP: 8.0
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/submission.php';
require_once __DIR__ . '/privacy.php';
require_once __DIR__ . '/metadata.php';

function pps_install(): void {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $collate = $wpdb->get_charset_collate();
    dbDelta("CREATE TABLE {$wpdb->prefix}pps_applications (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        reference_id char(36) NOT NULL,
        request_id char(32) NOT NULL,
        payload_hash char(64) NOT NULL,
        organisation_name varchar(160) NOT NULL,
        website varchar(500) NOT NULL DEFAULT '',
        contact_name varchar(120) NOT NULL,
        email varchar(254) NOT NULL,
        contact_method varchar(10) NOT NULL,
        phone varchar(40) NOT NULL DEFAULT '',
        status varchar(24) NOT NULL DEFAULT 'new',
        source varchar(64) NOT NULL DEFAULT 'website_sponsorship_form',
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY reference_id (reference_id),
        UNIQUE KEY request_id (request_id),
        KEY email (email(191)),
        KEY status_created (status,created_at)
    ) $collate;");
    dbDelta("CREATE TABLE {$wpdb->prefix}pps_rate_limits (
        bucket char(64) NOT NULL,
        attempts int unsigned NOT NULL DEFAULT 1,
        expires_at bigint unsigned NOT NULL,
        PRIMARY KEY  (bucket),
        KEY expires_at (expires_at)
    ) $collate;");
    $page = get_page_by_path('sponsorship');
    if (!$page) {
        $id = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'sponsorship', 'post_title' => 'Community Sponsorship', 'post_content' => '[puzzlepath_sponsorship]'], true);
        if (!is_wp_error($id)) update_option('pps_page_id', $id);
    } elseif (has_shortcode($page->post_content, 'puzzlepath_sponsorship')) { update_option('pps_page_id', $page->ID); }
    if (!wp_next_scheduled('pps_cleanup')) wp_schedule_event(time(), 'daily', 'pps_cleanup');
}
register_activation_hook(__FILE__, 'pps_install');
register_deactivation_hook(__FILE__, function () { wp_clear_scheduled_hook('pps_cleanup'); });
add_action('pps_cleanup', function () { global $wpdb; $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}pps_rate_limits WHERE expires_at < %d", time())); });
function pps_is_page(): bool { return is_page((int)get_option('pps_page_id')) && (int)get_option('pps_page_id') > 0; }
add_filter('body_class', function ($classes) { if (pps_is_page()) $classes[] = 'pps-page'; return $classes; });
add_action('wp_enqueue_scripts', function () {
    if (!pps_is_page()) return;
    wp_enqueue_style('pps', plugins_url('assets/sponsorship.css', __FILE__), [], '1.0.1');
    wp_enqueue_script('pps', plugins_url('assets/sponsorship.js', __FILE__), [], '1.0.1', true);
});
add_shortcode('puzzlepath_sponsorship', function () {
    $asset_url = plugins_url('assets/', __FILE__);
    $endpoint = admin_url('admin-ajax.php');
    $privacy_url = get_privacy_policy_url();
    $organisations = pps_public_organisations(apply_filters('pps_organisations', require __DIR__ . '/data/organisations.php'));
    ob_start(); require __DIR__ . '/view.php'; return ob_get_clean();
});
add_filter('pre_get_document_title', function ($title) { return pps_is_page() ? 'Community Sponsorship | Puzzle Path Scavenger Hunts' : $title; }, 99);
function pps_description(): string { return 'Discover how Puzzle Path supports schools, charities and community groups with complimentary scavenger hunt vouchers and Digital Golden Tickets. Request sponsorship today.'; }
add_filter('wpseo_metadesc', function ($description) { return pps_is_page() ? pps_description() : $description; });
add_filter('rank_math/frontend/description', function ($description) { return pps_is_page() ? pps_description() : $description; });
add_action('wp_head', function () {
    if (!pps_is_page() || defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION')) return;
    echo '<meta name="description" content="' . esc_attr(pps_description()) . '"><meta property="og:title" content="Community Sponsorship | Puzzle Path Scavenger Hunts"><meta property="og:description" content="' . esc_attr(pps_description()) . '"><meta property="og:type" content="website"><meta property="og:url" content="' . esc_url(get_permalink()) . '"><meta property="og:image" content="' . esc_url(plugins_url('assets/koko-transparent.png', __FILE__)) . '"><meta property="og:image:alt" content="Koko holding a magnifying glass and treasure map">';
});
// Reuse existing menu walkers, including Astra's mobile navigation.
add_filter('wp_nav_menu_objects', function ($items, $args) {
    $id = (int)get_option('pps_page_id');
    if (!$id || get_post_status($id) !== 'publish') return $items;
    $url = get_permalink($id); $eligible = in_array($args->theme_location ?? '', ['primary', 'mobile_menu', 'footer_menu'], true);
    foreach ($items as $item) {
        if (untrailingslashit($item->url) === untrailingslashit($url)) return $items;
        if (preg_match('~/(about|contact)/?$~', $item->url)) $eligible = true;
    }
    if (!$eligible) return $items;
    $item = new WP_Post((object)['ID' => -92001, 'post_title' => 'Sponsorship', 'post_type' => 'nav_menu_item']);
    foreach (['title' => 'Sponsorship', 'url' => $url, 'menu_item_parent' => 0, 'db_id' => 0, 'object_id' => $id, 'object' => 'page', 'type' => 'post_type', 'type_label' => 'Page', 'target' => '', 'attr_title' => '', 'description' => '', 'xfn' => '', 'classes' => ['menu-item', 'menu-item-type-post_type', 'menu-item-object-page'], 'current' => pps_is_page(), 'current_item_ancestor' => false, 'current_item_parent' => false] as $key => $value) $item->$key = $value;
    $items[] = $item; return $items;
}, 10, 2);
// The live Astra footer uses copyright content rather than a registered footer menu.
add_filter('astra_footer_copyright', function ($html) {
    $id = (int)get_option('pps_page_id');
    if ($id && get_post_status($id) === 'publish' && !str_contains($html, '/sponsorship')) $html .= '<p><a href="' . esc_url(get_permalink($id)) . '">Sponsorship</a></p>';
    return $html;
});
// The installed Astra footer stores its copyright as a paragraph block widget.
add_filter('widget_block_content', function ($content) {
    $id = (int)get_option('pps_page_id');
    if ($id && get_post_status($id) === 'publish' && str_contains(wp_strip_all_tags($content), 'Puzzle Path. All rights reserved.') && !str_contains($content, '/sponsorship')) {
        $content .= '<p class="has-text-align-center"><a href="' . esc_url(get_permalink($id)) . '">Sponsorship</a></p>';
    }
    return $content;
});
add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) return;
    if (!get_option('pps_page_id')) echo '<div class="notice notice-error"><p>Puzzle Path Sponsorship: the /sponsorship slug is already in use. Add [puzzlepath_sponsorship] to the intended page and reactivate this plugin.</p></div>';
    if (!get_privacy_policy_url()) echo '<div class="notice notice-warning"><p>Puzzle Path Sponsorship: select a published Privacy Policy under Settings → Privacy to enable sponsorship submissions.</p></div>';
});
