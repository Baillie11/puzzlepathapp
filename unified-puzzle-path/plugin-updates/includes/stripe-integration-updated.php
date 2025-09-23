<?php
defined('ABSPATH') or die('No script kiddies please!');

// This check ensures that the composer autoloader is present.
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>PuzzlePath Booking: The Stripe PHP library is not installed. Please run "composer install" in the plugin directory or install the plugin from the .zip file.</p></div>';
    });
    return;
}
require_once __DIR__ . '/../vendor/autoload.php';

class PuzzlePath_Stripe_Integration {

    private static $instance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Register settings fields
        add_action('admin_init', array($this, 'register_stripe_settings'));
        add_action('rest_api_init', array($this, 'register_rest_endpoints'));
        
        // Note: The admin_menu action to add the settings page is now in the main plugin file.
        // The callback points to 'stripe_settings_page_content'
    }

    /**
     * Register the settings fields for the Stripe settings page.
     */
    public function register_stripe_settings() {
        register_setting('puzzlepath_stripe_settings', 'puzzlepath_stripe_test_mode');
        register_setting('puzzlepath_stripe_settings', 'puzzlepath_stripe_publishable_key');
        register_setting('puzzlepath_stripe_settings', 'puzzlepath_stripe_secret_key');
        register_setting('puzzlepath_stripe_settings', 'puzzlepath_stripe_live_publishable_key');
        register_setting('puzzlepath_stripe_settings', 'puzzlepath_stripe_live_secret_key');
        register_setting('puzzlepath_stripe_settings', 'puzzlepath_stripe_webhook_secret');
    }

    public function register_rest_endpoints() {
        register_rest_route('puzzlepath/v1', '/payment/create-intent', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_payment_intent'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('puzzlepath/v1', '/stripe-webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true'
        ));

        // New endpoint to fetch booking code by payment intent
        register_rest_route('puzzlepath/v1', '/booking-status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_booking_status'),
            'permission_callback' => '__return_true'
        ));
        
        // New endpoint for free bookings (100% discount)
        register_rest_route('puzzlepath/v1', '/booking/free', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_free_booking'),
            'permission_callback' => '__return_true'
        ));
    }

    private function get_stripe_keys() {
        $test_mode = get_option('puzzlepath_stripe_test_mode', true);
        if ($test_mode) {
            return [
                'publishable' => get_option('puzzlepath_stripe_publishable_key'),
                'secret' => get_option('puzzlepath_stripe_secret_key'),
            ];
        } else {
            return [
                'publishable' => get_option('puzzlepath_stripe_live_publishable_key'),
                'secret' => get_option('puzzlepath_stripe_live_secret_key'),
            ];
        }
    }

    public function create_payment_intent($request) {
        global $wpdb;
        $params = $request->get_json_params();

        if (empty($params['event_id']) || empty($params['tickets'])) {
            return new WP_Error('missing_params', 'Missing event_id or tickets', array('status' => 400));
        }

        $event_id = intval($params['event_id']);
        $tickets = intval($params['tickets']);
        $coupon_code = isset($params['coupon_code']) ? sanitize_text_field($params['coupon_code']) : null;

        // Get event details including hunt information
        $event = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pp_events WHERE id = %d", $event_id));

        if (!$event || $event->seats < $tickets) {
            return new WP_Error('invalid_event', 'Event not found or not enough seats.', array('status' => 400));
        }

        $total_price = $event->price * $tickets;
        $coupon_id = null;

        if ($coupon_code) {
            $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pp_coupons WHERE code = %s AND (expires_at IS NULL OR expires_at > NOW()) AND (max_uses = 0 OR times_used < max_uses)", $coupon_code));
            if ($coupon) {
                $total_price = $total_price - ($total_price * ($coupon->discount_percent / 100));
                $coupon_id = $coupon->id;
            }
        }

        $stripe_keys = $this->get_stripe_keys();
        \Stripe\Stripe::setApiKey($stripe_keys['secret']);

        try {
            // Generate a unique booking code based on hunt
            $booking_code = $this->generate_hunt_booking_code($event);
            
            // Link to hunt if hunt integration is enabled
            $hunt_id = null;
            if ($event->hunt_code && $event->hosting_type === 'self_hosted') {
                // Try to find matching hunt in the unified app's hunt table
                $hunt_id = $this->get_hunt_id_by_code($event->hunt_code);
            }
            
            // Create a pending booking first
            $booking_data = [
                'event_id' => $event_id,
                'customer_name' => sanitize_text_field($params['name']),
                'customer_email' => sanitize_email($params['email']),
                'tickets' => $tickets,
                'total_price' => $total_price,
                'coupon_id' => $coupon_id,
                'payment_status' => 'pending',
                'booking_code' => $booking_code,
                'hunt_id' => $hunt_id,
                'participant_count' => $tickets,
                'booking_date' => date('Y-m-d')
            ];
            
            $wpdb->insert("{$wpdb->prefix}pp_bookings", $booking_data);
            $booking_id = $wpdb->insert_id;

            $payment_intent = \Stripe\PaymentIntent::create([
                'amount' => $total_price * 100, // Amount in cents
                'currency' => 'aud',
                'metadata' => [
                    'booking_id' => $booking_id,
                    'event_id' => $event_id,
                    'tickets' => $tickets,
                    'hunt_code' => $event->hunt_code ?? '',
                    'hunt_name' => $event->hunt_name ?? '',
                ],
            ]);

            // Update booking with payment intent ID
            $wpdb->update("{$wpdb->prefix}pp_bookings", 
                ['stripe_payment_intent_id' => $payment_intent->id],
                ['id' => $booking_id]
            );

            return new WP_REST_Response([
                'clientSecret' => $payment_intent->client_secret,
                'bookingId' => $booking_id,
                'bookingCode' => $booking_code,
                'huntCode' => $event->hunt_code,
                'huntName' => $event->hunt_name,
                'isQuestEvent' => ($event->hunt_code && $event->hosting_type === 'self_hosted')
            ], 200);

        } catch (Exception $e) {
            return new WP_Error('stripe_error', $e->getMessage(), array('status' => 500));
        }
    }

    public function handle_webhook($request) {
        $payload = $request->get_body();
        $sig_header = $request->get_header('stripe_signature');
        $endpoint_secret = get_option('puzzlepath_stripe_webhook_secret');
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch(\UnexpectedValueException $e) {
            return new WP_Error('invalid_payload', 'Invalid payload', array('status' => 400));
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            return new WP_Error('invalid_signature', 'Invalid signature', array('status' => 400));
        }

        if ($event->type == 'charge.succeeded') {
            $payment_intent = $event->data->object;
            $booking_code = $this->fulfill_booking($payment_intent->id);
            return new WP_REST_Response(array('status' => 'success', 'booking_code' => $booking_code), 200);
        }

        return new WP_REST_Response(array('status' => 'success'), 200);
    }
    
    private function fulfill_booking($payment_intent_id) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'pp_bookings';
        $events_table = $wpdb->prefix . 'pp_events';
        $coupons_table = $wpdb->prefix . 'pp_coupons';

        $booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM $bookings_table WHERE stripe_payment_intent_id = %s", $payment_intent_id));

        if ($booking && $booking->payment_status === 'pending') {
            // Update payment status to 'paid' for unified app compatibility
            $wpdb->update($bookings_table, 
                [
                    'payment_status' => 'paid' // Changed from 'succeeded' to 'paid'
                ], 
                ['id' => $booking->id]
            );

            // Decrement seat count
            $wpdb->query($wpdb->prepare("UPDATE $events_table SET seats = seats - %d WHERE id = %d", $booking->tickets, $booking->event_id));

            // Increment coupon usage
            if ($booking->coupon_id) {
                $wpdb->query($wpdb->prepare("UPDATE $coupons_table SET times_used = times_used + 1 WHERE id = %d", $booking->coupon_id));
            }

            // Send confirmation email with quest link if applicable
            $this->send_confirmation_email($booking, $booking->booking_code);

            return $booking->booking_code;
        }
        return null;
    }

    private function send_confirmation_email($booking, $booking_code) {
        $to = $booking->customer_email;
        $subject = 'Your PuzzlePath Booking Confirmation';
        $quest_link = '';
        
        global $wpdb;
        $event = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pp_events WHERE id = %d", $booking->event_id));
        
        if ($event && $event->hunt_code && $event->hosting_type === 'self_hosted') {
            $unified_app_url = get_option('puzzlepath_unified_app_url', '');
            if ($unified_app_url) {
                $quest_link = rtrim($unified_app_url, '/');
            }
        }
        
        // Get HTML email template (this function should be available from settings.php)
        if (function_exists('get_email_template')) {
            $message = get_email_template($booking, $booking_code, $event, $quest_link);
            
            // Set headers for HTML email
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: PuzzlePath Team <info@puzzlepath.com.au>'
            );
            
            wp_mail($to, $subject, $message, $headers);
        } else {
            // Fallback to plain text if template function not available
            $event_title = $event ? $event->title : 'Your Event';
            $event_date = $event ? $event->event_date : 'TBD';
            
            $plain_quest_link = '';
            if ($quest_link) {
                $hunt_name = $event ? $event->hunt_name : 'adventure';
                $plain_quest_link = "\n\n🎯 START YOUR QUEST:\nReady to begin your {$hunt_name}?\nClick here: {$quest_link}?booking={$booking_code}\nUse your booking code: {$booking_code}\n";
            }
            
            $message = "Dear {$booking->customer_name},\n\nThank you for your booking!\n\nBooking Details:\nEvent: {$event_title}\nDate: {$event_date}\nPrice: $".$booking->total_price."\nBooking Code: {$booking_code}{$plain_quest_link}\n\nRegards,\nPuzzlePath Team";
            
            wp_mail($to, $subject, $message);
        }
    }

    /**
     * Generates a unique booking code based on hunt information.
     */
    private function generate_hunt_booking_code($event) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'pp_bookings';
        
        $prefix = 'PP'; // Default prefix
        
        // Use hunt code if available
        if ($event->hunt_code && $event->hosting_type === 'self_hosted') {
            $prefix = strtoupper($event->hunt_code);
        }
        
        $date_part = date('Ymd');
        
        do {
            // Generate: [HUNT_CODE]-[YYYYMMDD]-[4-digit-number]
            $number = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $code = $prefix . '-' . $date_part . '-' . $number;
            
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $bookings_table WHERE booking_code = %s", $code));
        } while ($exists > 0);
        
        return $code;
    }

    /**
     * Get hunt ID from unified app's hunt table
     */
    private function get_hunt_id_by_code($hunt_code) {
        global $wpdb;
        
        // Try to find hunt in the unified app's pp_hunts table
        $hunt_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}pp_hunts WHERE hunt_code = %s AND is_active = TRUE", 
            $hunt_code
        ));
        
        return $hunt_id ? intval($hunt_id) : null;
    }

    public function get_booking_status($request) {
        global $wpdb;
        $payment_intent_id = $request->get_param('payment_intent');
        if (!$payment_intent_id) {
            return new WP_Error('missing_param', 'Missing payment_intent parameter', array('status' => 400));
        }
        $booking = $wpdb->get_row($wpdb->prepare("SELECT booking_code, payment_status FROM {$wpdb->prefix}pp_bookings WHERE stripe_payment_intent_id = %s", $payment_intent_id));
        if (!$booking) {
            return new WP_REST_Response(['status' => 'pending'], 200);
        }
        if (in_array($booking->payment_status, ['succeeded', 'paid']) && $booking->booking_code) {
            return new WP_REST_Response(['status' => 'succeeded', 'booking_code' => $booking->booking_code], 200);
        }
        return new WP_REST_Response(['status' => $booking->payment_status], 200);
    }

    /**
     * Process free bookings (100% discount) without going through Stripe
     */
    public function process_free_booking($request) {
        global $wpdb;
        $params = $request->get_json_params();

        // Validate required parameters
        if (empty($params['event_id']) || empty($params['tickets']) || empty($params['name']) || empty($params['email'])) {
            return new WP_Error('missing_params', 'Missing required parameters', array('status' => 400));
        }

        $event_id = intval($params['event_id']);
        $tickets = intval($params['tickets']);
        $coupon_code = isset($params['coupon_code']) ? sanitize_text_field($params['coupon_code']) : null;

        // Get event details
        $event = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pp_events WHERE id = %d", $event_id));

        if (!$event || $event->seats < $tickets) {
            return new WP_Error('invalid_event', 'Event not found or not enough seats.', array('status' => 400));
        }

        $total_price = $event->price * $tickets;
        $coupon_id = null;

        // Apply coupon if provided
        if ($coupon_code) {
            $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pp_coupons WHERE code = %s AND (expires_at IS NULL OR expires_at > NOW()) AND (max_uses = 0 OR times_used < max_uses)", $coupon_code));
            if ($coupon) {
                $total_price = $total_price - ($total_price * ($coupon->discount_percent / 100));
                $coupon_id = $coupon->id;
            }
        }

        // Only process if the total price is 0 (100% discount)
        if ($total_price > 0) {
            return new WP_Error('not_free', 'This endpoint is only for free bookings (100% discount)', array('status' => 400));
        }

        try {
            // Generate a unique booking code
            $booking_code = $this->generate_hunt_booking_code($event);
            
            // Get hunt ID if applicable
            $hunt_id = null;
            if ($event->hunt_code && $event->hosting_type === 'self_hosted') {
                $hunt_id = $this->get_hunt_id_by_code($event->hunt_code);
            }
            
            // Create booking with 'paid' status since it's free
            $booking_data = [
                'event_id' => $event_id,
                'customer_name' => sanitize_text_field($params['name']),
                'customer_email' => sanitize_email($params['email']),
                'tickets' => $tickets,
                'total_price' => 0.00,
                'coupon_id' => $coupon_id,
                'payment_status' => 'paid', // Mark as paid since it's free
                'booking_code' => $booking_code,
                'hunt_id' => $hunt_id,
                'participant_count' => $tickets,
                'booking_date' => date('Y-m-d')
            ];
            
            $wpdb->insert("{$wpdb->prefix}pp_bookings", $booking_data);
            $booking_id = $wpdb->insert_id;

            if (!$booking_id) {
                return new WP_Error('booking_failed', 'Failed to create booking', array('status' => 500));
            }

            // Get the booking object for email
            $booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pp_bookings WHERE id = %d", $booking_id));
            
            // Decrement seat count
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}pp_events SET seats = seats - %d WHERE id = %d", $tickets, $event_id));

            // Increment coupon usage
            if ($coupon_id) {
                $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}pp_coupons SET times_used = times_used + 1 WHERE id = %d", $coupon_id));
            }

            // Send confirmation email
            $this->send_confirmation_email($booking, $booking_code);

            return new WP_REST_Response([
                'success' => true,
                'booking_id' => $booking_id,
                'booking_code' => $booking_code,
                'hunt_code' => $event->hunt_code ?? '',
                'hunt_name' => $event->hunt_name ?? '',
                'is_quest_event' => ($event->hunt_code && $event->hosting_type === 'self_hosted')
            ], 200);

        } catch (Exception $e) {
            return new WP_Error('booking_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Display the Stripe settings page content.
     * This function is called by the add_submenu_page in the main plugin file.
     */
    public function stripe_settings_page_content() {
        ?>
        <div class="wrap">
            <h1>Stripe Payment Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('puzzlepath_stripe_settings'); ?>
                <?php do_settings_sections('puzzlepath-stripe-settings'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Test Mode</th>
                        <td>
                            <input type="checkbox" name="puzzlepath_stripe_test_mode" value="1" 
                                   <?php checked(get_option('puzzlepath_stripe_test_mode', true)); ?>>
                            <p class="description">Enable test mode for development. Use test keys and test card numbers.</p>
                        </td>
                    </tr>
                 
                    <tr valign="top">
                        <th scope="row">Test Publishable Key</th>
                        <td><input type="text" name="puzzlepath_stripe_publishable_key" value="<?php echo esc_attr( get_option('puzzlepath_stripe_publishable_key') ); ?>" class="regular-text"/></td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">Test Secret Key</th>
                        <td><input type="password" name="puzzlepath_stripe_secret_key" value="<?php echo esc_attr( get_option('puzzlepath_stripe_secret_key') ); ?>" class="regular-text"/></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Live Publishable Key</th>
                        <td><input type="text" name="puzzlepath_stripe_live_publishable_key" value="<?php echo esc_attr( get_option('puzzlepath_stripe_live_publishable_key') ); ?>" class="regular-text"/></td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">Live Secret Key</th>
                        <td><input type="password" name="puzzlepath_stripe_live_secret_key" value="<?php echo esc_attr( get_option('puzzlepath_stripe_live_secret_key') ); ?>" class="regular-text"/></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Webhook Signing Secret</th>
                        <td>
                            <input type="password" name="puzzlepath_stripe_webhook_secret" value="<?php echo esc_attr( get_option('puzzlepath_stripe_webhook_secret') ); ?>" class="regular-text"/>
                            <p class="description">Get this from your Stripe webhook settings. Ensures payment notifications are genuinely from Stripe.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>

            <h2>Webhook Setup</h2>
            <p>For Stripe to notify your site about payment status, you must set up a webhook in your Stripe Dashboard.</p>
            <p>1. Go to your <a href="https://dashboard.stripe.com/webhooks" target="_blank">Stripe Webhooks settings</a>.</p>
            <p>2. Click "Add an endpoint".</p>
            <p>3. Enter the following URL for the endpoint:</p>
            <p><code><?php echo home_url('/wp-json/puzzlepath/v1/stripe-webhook'); ?></code></p>
            <p>4. Click "Select events" and choose the following event:</p>
            <p><code>charge.succeeded</code></p>
            <p>5. Click "Add endpoint".</p>
            <p>6. After creating the endpoint, find the "Signing secret" and paste it into the "Webhook Signing Secret" field above.</p>
            
            <h2>Booking Code Format</h2>
            <div style="background: #f0f8ff; border: 1px solid #0073aa; border-radius: 5px; padding: 15px; margin: 20px 0;">
                <p><strong>Hunt-Integrated Booking Codes:</strong></p>
                <ul>
                    <li><code>[HUNT_CODE]-[YYYYMMDD]-[NUMBER]</code> - For quest events with hunt codes</li>
                    <li><code>PP-[YYYYMMDD]-[NUMBER]</code> - For regular events without hunt integration</li>
                </ul>
                <p><em>Example: BB-20250108-1234 (Broadbeach quest booked on Jan 8, 2025)</em></p>
            </div>
        </div>
        <?php
    }

    // ... other methods for payment intent, webhook handling etc.
}

// Initialize the class
PuzzlePath_Stripe_Integration::get_instance();
?>
