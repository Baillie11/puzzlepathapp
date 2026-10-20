<?php
// Include once from the active theme after removing the previous tester notice callback.
defined('ABSPATH') || exit;
add_action('admin_notices', function() {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'puzzlepath_page_puzzlepath-bookings' || !get_option('puzzlepath_tester_access_key')) {
        return;
    }
    echo '<p style="font-size:12px;margin:8px 0"><a href="#pp-tester-invitation" id="pp-tester-jump">Goto Tester link</a></p>';
});

add_action('admin_footer', function() {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'puzzlepath_page_puzzlepath-bookings') {
        return;
    }
    $key = get_option('puzzlepath_tester_access_key');
    if (!$key) {
        return;
    }
    $invite_url = add_query_arg('key', $key, home_url('/tester-registration/'));
    ?>
    <section id="pp-tester-invitation" class="pp-tester-access" tabindex="-1" aria-label="Tester invitation" style="clear:both;margin:24px 20px 24px 0;padding:16px 20px;background:#fff;border:1px solid #c3c4c7;border-left:4px solid #72aee6;scroll-margin-top:60px">
        <h2>Tester invitation</h2>
        <p><strong>Tester Access Key:</strong> <code style="overflow-wrap:anywhere"><?php echo esc_html($key); ?></code></p>
        <details open style="margin:8px 0 12px;max-width:760px">
            <summary style="cursor:pointer;min-height:44px;display:flex;align-items:center;color:#135e96" title="Tap or click to learn what the tester key does and how to use it."><span aria-hidden="true" style="margin-right:6px">&#9432;</span> What is this and how do I use it?</summary>
            <p>This key lets invited testers register for a free double pass (two tickets) for each currently available hunt with enough spaces. They enter their name and email, then receive their booking codes and hunt start links by email. These bookings appear here as tester bookings and reserve two spaces per hunt.</p>
            <p><strong>How to use it:</strong> Copy the complete invitation link below and send it to the people you want to test your hunts. They can register without a WordPress login. Each email address can register once.</p>
            <p><label for="pp-tester-invite"><strong>Tester invitation link</strong></label><br>
            <input id="pp-tester-invite" type="text" readonly value="<?php echo esc_attr($invite_url); ?>" style="width:100%;min-height:44px;margin-top:6px" onclick="this.select()" aria-describedby="pp-tester-invite-help"></p>
            <p><button type="button" class="button button-primary" id="pp-copy-tester-invite" style="min-height:44px;padding:6px 16px">Copy invitation link</button>
            <span id="pp-copy-tester-status" role="status" aria-live="polite" style="display:block;margin-top:8px"></span></p>
            <p id="pp-tester-invite-help">Copy and share this link privately with your testers. Anyone with this link can claim the tester passes. This is separate from your discount portal password.</p>
        </details>
    </section>
    <script>
    (function () {
        var section = document.getElementById('pp-tester-invitation');
        var content = document.getElementById('wpbody-content');
        if (content) { content.appendChild(section); }
        document.getElementById('pp-tester-jump').addEventListener('click', function () {
            section.querySelector('details').open = true;
            section.focus({preventScroll: true});
        });
        var button = document.getElementById('pp-copy-tester-invite');
        var field = document.getElementById('pp-tester-invite');
        var status = document.getElementById('pp-copy-tester-status');
        button.addEventListener('click', async function () {
            status.textContent = '';
            var copied = false;
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(field.value);
                    copied = true;
                }
            } catch (error) {}
            if (!copied) {
                field.focus();
                field.select();
                field.setSelectionRange(0, field.value.length);
                try { copied = document.execCommand('copy'); } catch (error) {}
            }
            status.textContent = copied
                ? 'Invitation link copied! Paste it into a message to your tester.'
                : 'Select and hold the link above, then choose Copy.';
            if (copied) { button.focus(); }
        });
    }());
    </script>
    <?php
});
