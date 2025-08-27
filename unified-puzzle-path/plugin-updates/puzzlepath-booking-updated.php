<?php
/**
 * Plugin Name: PuzzlePath Booking
 * Description: A custom booking plugin for PuzzlePath with unified quest integration.
 * Version: 2.4.0
 * Author: Andrew Baillie
 */

defined('ABSPATH') or die('No script kiddies please!');

// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/events.php';
require_once plugin_dir_path(__FILE__) . 'includes/coupons.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/stripe-integration.php';

/**
 * Activation hook to create/update database tables.
 */
function puzzlepath_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Events Table - Updated with hunt integration
    $table_name = $wpdb->prefix . 'pp_events';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        title varchar(255) NOT NULL,
        hosting_type varchar(20) DEFAULT 'hosted' NOT NULL,
        event_date datetime,
        location varchar(255) NOT NULL,
        price float NOT NULL,
        seats int(11) NOT NULL,
        hunt_code varchar(10) DEFAULT NULL,
        hunt_name varchar(100) DEFAULT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Coupons Table
    $table_name = $wpdb->prefix . 'pp_coupons';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        code varchar(50) NOT NULL,
        discount_percent int(3) NOT NULL,
        max_uses int(11) DEFAULT 0 NOT NULL,
        times_used int(11) DEFAULT 0 NOT NULL,
        expires_at datetime,
        PRIMARY KEY  (id),
        UNIQUE KEY code (code)
    ) $charset_collate;";
    dbDelta($sql);

    // Bookings Table - Updated for unified app compatibility
    $table_name = $wpdb->prefix . 'pp_bookings';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        event_id mediumint(9) NOT NULL,
        customer_name varchar(255) NOT NULL,
        customer_email varchar(255) NOT NULL,
        tickets int(11) NOT NULL,
        total_price float NOT NULL,
        coupon_id mediumint(9),
        payment_status varchar(50) DEFAULT 'pending' NOT NULL,
        stripe_payment_intent_id varchar(255),
        booking_code varchar(20),
        hunt_id int(11) DEFAULT NULL,
        participant_names text DEFAULT NULL,
        participant_count int(11) DEFAULT 1,
        booking_date date DEFAULT NULL,
        special_requirements text DEFAULT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql);
    
    // Create view for unified app compatibility
    $view_name = $wpdb->prefix . '2s_pp_bookings';
    $source_table = $wpdb->prefix . 'pp_bookings';
    
    $wpdb->query("DROP VIEW IF EXISTS $view_name");
    $wpdb->query("
        CREATE VIEW $view_name AS 
        SELECT 
            id,
            created_at,
            event_id,
            customer_name,
            customer_email,
            tickets,
            total_price,
            coupon_id,
            payment_status,
            stripe_payment_intent_id,
            booking_code,
            hunt_id,
            participant_names,
            participant_count,
            booking_date,
            special_requirements
        FROM $source_table
    ");
    
    update_option('puzzlepath_booking_version', '2.4.0');
}
register_activation_hook(__FILE__, 'puzzlepath_activate');

/**
 * Check if database needs updating on plugin load.
 */
function puzzlepath_update_db_check() {
    $current_version = get_option('puzzlepath_booking_version', '1.0');
    if (version_compare($current_version, '2.4.0', '<')) {
        puzzlepath_activate();
    }
}
add_action('plugins_loaded', 'puzzlepath_update_db_check');

/**
 * Centralized function to create all admin menus.
 */
function puzzlepath_register_admin_menus() {
    add_menu_page('PuzzlePath Bookings', 'PuzzlePath', 'manage_options', 'puzzlepath-booking', 'puzzlepath_events_page', 'dashicons-tickets-alt', 20);
    add_submenu_page('puzzlepath-booking', 'Events', 'Events', 'manage_options', 'puzzlepath-events', 'puzzlepath_events_page');
    add_submenu_page('puzzlepath-booking', 'Coupons', 'Coupons', 'manage_options', 'puzzlepath-coupons', 'puzzlepath_coupons_page');
    if (class_exists('PuzzlePath_Stripe_Integration')) {
        $stripe_instance = PuzzlePath_Stripe_Integration::get_instance();
        add_submenu_page('puzzlepath-booking', 'Stripe Settings', 'Stripe Settings', 'manage_options', 'puzzlepath-stripe-settings', array($stripe_instance, 'stripe_settings_page_content'));
    }
    remove_submenu_page('puzzlepath-booking', 'puzzlepath-booking');
}
add_action('admin_menu', 'puzzlepath_register_admin_menus');

/**
 * Handle non-payment form submission (deprecated, but kept for safety).
 */
function puzzlepath_handle_booking_submission() {
    // This is now handled by the Stripe payment flow.
}
add_action('init', 'puzzlepath_handle_booking_submission');

/**
 * Enqueue scripts and styles.
 */
function puzzlepath_enqueue_scripts() {
    if (is_admin()) {
        wp_enqueue_script('jquery');
        return;
    }

    global $post;
    if ($post && has_shortcode($post->post_content, 'puzzlepath_booking_form')) {
        wp_enqueue_style(
            'puzzlepath-booking-form-style',
            plugin_dir_url(__FILE__) . 'css/booking-form.css',
            array(),
            '1.0.2' // updated version
        );
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, true);
        
        wp_enqueue_script(
            'puzzlepath-booking-form',
            plugin_dir_url(__FILE__) . 'js/booking-form.js',
            array('jquery'),
            '1.2.4', // updated version
            true
        );
        
        wp_enqueue_script(
            'puzzlepath-stripe-payment',
            plugin_dir_url(__FILE__) . 'js/stripe-payment.js',
            array('jquery', 'stripe-js'),
            '1.3.0', // updated version
            true
        );

        $test_mode = get_option('puzzlepath_stripe_test_mode', true);
        $publishable_key = $test_mode ? get_option('puzzlepath_stripe_publishable_key') : get_option('puzzlepath_stripe_live_publishable_key');

        wp_localize_script(
            'puzzlepath-stripe-payment',
            'puzzlepath_data',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'coupon_nonce' => wp_create_nonce('puzzlepath_coupon_nonce'),
                'publishable_key' => $publishable_key,
                'rest_url' => rest_url('puzzlepath/v1/'),
                'rest_nonce' => wp_create_nonce('wp_rest'),
                'unified_app_url' => get_option('puzzlepath_unified_app_url', '') // Add this setting
            )
        );
    }
}
add_action('wp_enqueue_scripts', 'puzzlepath_enqueue_scripts');

/**
 * AJAX handler for applying a coupon.
 */
function puzzlepath_apply_coupon_callback() {
    check_ajax_referer('puzzlepath_coupon_nonce', 'nonce');
    global $wpdb;
    $coupons_table = $wpdb->prefix . 'pp_coupons';
    $code = sanitize_text_field($_POST['coupon_code']);
    
    $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $coupons_table WHERE code = %s", $code));

    if (!$coupon) {
        wp_send_json_error(['message' => 'Invalid coupon code.']);
        return;
    }
    if ($coupon->expires_at && strtotime($coupon->expires_at) < time()) {
        wp_send_json_error(['message' => 'This coupon has expired.']);
        return;
    }
    if ($coupon->max_uses > 0 && $coupon->times_used >= $coupon->max_uses) {
        wp_send_json_error(['message' => 'This coupon has reached its usage limit.']);
        return;
    }
    wp_send_json_success([
        'discount_percent' => $coupon->discount_percent,
        'code' => $coupon->code
    ]);
}
add_action('wp_ajax_apply_coupon', 'puzzlepath_apply_coupon_callback');
add_action('wp_ajax_nopriv_apply_coupon', 'puzzlepath_apply_coupon_callback');

/**
 * The main shortcode for displaying the booking form.
 */
function puzzlepath_booking_form_shortcode() {
    global $wpdb;
    $events_table = $wpdb->prefix . 'pp_events';
    $events = $wpdb->get_results("SELECT * FROM $events_table WHERE seats > 0 ORDER BY event_date ASC");

    ob_start();
    ?>
    <div id="puzzlepath-booking-form-container">
        <form id="puzzlepath-booking-form">

            <label for="event_id">Choose your adventure:</label>
            <select name="event_id" id="event_id" required>
                <option value="">Select an event</option>
                <?php foreach ($events as $event) : ?>
                    <option value="<?php echo esc_attr($event->id); ?>" 
                            data-price="<?php echo esc_attr($event->price); ?>" 
                            data-seats="<?php echo esc_attr($event->seats); ?>"
                            data-hunt-code="<?php echo esc_attr($event->hunt_code); ?>"
                            data-hunt-name="<?php echo esc_attr($event->hunt_name); ?>">
                        <?php echo esc_html($event->title); ?>
                        <?php if ($event->hunt_name): ?>
                            (<?php echo esc_html($event->hunt_name); ?>)
                        <?php endif; ?>
                        - $<?php echo esc_html(number_format($event->price, 2)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="tickets">Number of people:</label>
            <input type="number" id="tickets" name="tickets" min="1" required>

            <label for="customer_name">Your Name:</label>
            <input type="text" id="customer_name" name="customer_name" required>

            <label for="customer_email">Your Email:</label>
            <input type="email" id="customer_email" name="customer_email" required>
            
            <div id="coupon-container">
                <label for="coupon_code" class="screen-reader-text">Discount Code</label>
                <input type="text" id="coupon_code" name="coupon_code" placeholder="Discount Code">
                <button type="button" id="apply-coupon-btn">Apply</button>
            </div>
            <p id="coupon-feedback"></p>

            <div id="total-price-container">
                Total Price: <span id="total-price">$0.00</span>
            </div>

            <!-- Stripe Card Elements -->
            <div class="form-row">
                <label for="card-number-element">Card Number</label>
                <div id="card-number-element" class="stripe-element"></div>
            </div>
            <div class="form-row-split">
                <div class="form-col">
                    <label for="card-expiry-element">Expiry Date</label>
                    <div id="card-expiry-element" class="stripe-element"></div>
                </div>
                <div class="form-col">
                    <label for="card-cvc-element">CVC</label>
                    <div id="card-cvc-element" class="stripe-element"></div>
                </div>
            </div>
             <div class="form-row">
                <label for="postal-code-element">Postcode</label>
                <div id="postal-code-element" class="stripe-element"></div>
            </div>

            <!-- Used to display form errors -->
            <div id="card-errors" role="alert"></div>

            <button id="submit-payment-btn">Pay Now</button>
        </form>
        <div id="payment-success-message" style="display: none;">
            <h2>Payment Successful!</h2>
            <p>Thank you for your booking. A confirmation email has been sent to you.</p>
            <div id="quest-link-container" style="display: none;">
                <h3>🎯 Ready to Start Your Quest?</h3>
                <p>Click the button below to begin your adventure:</p>
                <a id="start-quest-btn" href="#" target="_blank" class="quest-start-button">🚀 Start Quest Now!</a>
            </div>
        </div>
    </div>
    <style>
        .form-row-split { display: flex; gap: 15px; }
        .form-col { flex: 1; }
        .stripe-element {
            background-color: white;
            padding: 10px 12px;
            border-radius: 4px;
            border: 1px solid #ccc;
            margin-bottom: 1em;
        }
        #card-errors {
            color: #dc3545;
            margin: 10px 0;
        }
        .quest-start-button {
            display: inline-block;
            background: linear-gradient(135deg, #6a82fb 0%, #fc5c7d 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            font-size: 18px;
            margin: 10px 0;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .quest-start-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
            text-decoration: none;
        }
        #quest-link-container {
            background: linear-gradient(145deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #28a745;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        #quest-link-container h3 {
            color: #28a745;
            margin-top: 0;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('puzzlepath_booking_form', 'puzzlepath_booking_form_shortcode');

/**
 * Add settings for unified app integration
 */
function puzzlepath_unified_settings_init() {
    add_settings_section(
        'puzzlepath_unified_section',
        'Unified Quest App Integration',
        'puzzlepath_unified_section_callback',
        'puzzlepath-unified-settings'
    );
    
    add_settings_field(
        'puzzlepath_unified_app_url',
        'Unified App URL',
        'puzzlepath_unified_app_url_callback',
        'puzzlepath-unified-settings',
        'puzzlepath_unified_section'
    );
    
    register_setting('puzzlepath_unified_settings', 'puzzlepath_unified_app_url');
}
add_action('admin_init', 'puzzlepath_unified_settings_init');

function puzzlepath_unified_section_callback() {
    echo '<p>Configure integration with the unified Puzzle Path quest application.</p>';
}

function puzzlepath_unified_app_url_callback() {
    $value = get_option('puzzlepath_unified_app_url', '');
    echo '<input type="url" name="puzzlepath_unified_app_url" value="' . esc_attr($value) . '" class="regular-text" placeholder="https://yoursite.com/unified-puzzle-path/" />';
    echo '<p class="description">Enter the URL where your unified Puzzle Path app is installed.</p>';
}

/**
 * Add unified settings page to admin menu
 */
function puzzlepath_add_unified_settings_page() {
    add_submenu_page(
        'puzzlepath-booking',
        'Unified App Settings',
        'Unified App',
        'manage_options',
        'puzzlepath-unified-settings',
        'puzzlepath_unified_settings_page'
    );
}
add_action('admin_menu', 'puzzlepath_add_unified_settings_page');

function puzzlepath_unified_settings_page() {
    ?>
    <div class="wrap">
        <h1>Unified Quest App Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('puzzlepath_unified_settings'); ?>
            <?php do_settings_sections('puzzlepath-unified-settings'); ?>
            <?php submit_button(); ?>
        </form>
        
        <h2>Integration Status</h2>
        <div class="notice notice-info">
            <p><strong>Hunt Codes Available:</strong></p>
            <?php
            global $wpdb;
            $events_table = $wpdb->prefix . 'pp_events';
            $hunt_codes = $wpdb->get_results("SELECT DISTINCT hunt_code, hunt_name FROM $events_table WHERE hunt_code IS NOT NULL");
            if ($hunt_codes) {
                echo '<ul>';
                foreach ($hunt_codes as $hunt) {
                    echo '<li><strong>' . esc_html($hunt->hunt_code) . '</strong>: ' . esc_html($hunt->hunt_name) . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>No hunt codes configured. Edit your events to add hunt codes.</p>';
            }
            ?>
        </div>
    </div>
    <?php
}
?>
