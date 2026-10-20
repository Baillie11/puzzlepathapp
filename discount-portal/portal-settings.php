<?php
defined('ABSPATH') || exit;
function ppdp_password_hash() {
    return defined('PUZZLEPATH_PORTAL_PASSWORD_HASH') ? PUZZLEPATH_PORTAL_PASSWORD_HASH : (string)get_option('ppdp_password_hash', '');
}
add_action('admin_menu', function () {
    add_options_page('Puzzle Path Portal', 'Puzzle Path Portal', 'manage_options', 'puzzlepath-portal', 'ppdp_settings');
});
function ppdp_settings() {
    if (!current_user_can('manage_options')) wp_die('Administrator access required.');
    $message = ''; $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_admin_referer('ppdp_password_setup');
        if (!is_ssl()) { $error = 'Use HTTPS to set the portal password.'; }
        elseif (defined('PUZZLEPATH_PORTAL_PASSWORD_HASH')) { $error = 'Your password is managed in wp-config.php.'; }
        else {
            $password = isset($_POST['portal_password']) && is_string($_POST['portal_password']) ? wp_unslash($_POST['portal_password']) : '';
            $confirm = isset($_POST['portal_confirm']) && is_string($_POST['portal_confirm']) ? wp_unslash($_POST['portal_confirm']) : '';
            if (strlen($password) < 12 || strlen($password) > 72) { $error = 'Choose a unique password between 12 and 72 bytes (ordinary letters and numbers use one byte each).'; }
            elseif (!hash_equals($password, $confirm)) { $error = 'The passwords do not match.'; }
            else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                if (update_option('ppdp_password_hash', $hash, false)) $message = 'Portal password saved. You can now open the discount portal. Previous portal sessions and passkeys have been revoked.';
                else $error = 'Unable to save the password. Please try again.';
            }
            unset($password, $confirm);
        }
    }
    echo '<div class="wrap"><h1>Puzzle Path Discount Portal</h1>';
    if ($message) echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
    if ($error) echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
    echo '<p>Status: <strong>' . (ppdp_password_hash() ? 'Password configured' : 'Installed and active — set your portal password below') . '</strong></p>';
    echo '<p>Set a separate password once, then use the mobile portal without logging into WordPress. Only a password hash is stored.</p>';
    if (!defined('PUZZLEPATH_PORTAL_PASSWORD_HASH')) {
        echo '<form method="post" action="' . esc_url(admin_url('options-general.php?page=puzzlepath-portal')) . '">';
        wp_nonce_field('ppdp_password_setup');
        echo '<p><label for="portal_password">New portal password (at least 12 characters)</label><br><input class="regular-text" id="portal_password" name="portal_password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required></p>';
        echo '<p><label for="portal_confirm">Confirm portal password</label><br><input class="regular-text" id="portal_confirm" name="portal_confirm" type="password" autocomplete="new-password" minlength="12" maxlength="72" required></p>';
        submit_button('Save portal password');
        echo '</form>';
    }
    echo '<p><a class="button" href="' . esc_url(home_url('/?puzzlepath-discounts=1')) . '">Open discount portal</a></p>';
    echo '<p>Add the portal to your phone’s home screen for quick access. Codes apply across all hunts. Changing the password signs out existing portal sessions and revokes all registered passkeys.</p></div>';
}
