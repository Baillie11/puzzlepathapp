<?php defined('ABSPATH') || exit; ?>
<?php if ($signed_in): ?>
<section class="card passkey-card" id="passkeys" aria-labelledby="passkey-heading" hidden>
<?php else: ?>
<details class="card passkey-card" id="passkeys" open><summary>Sign in with a passkey</summary>
<?php endif; ?>
<h2 id="passkey-heading"><?= $signed_in ? 'Use your fingerprint next time' : 'Sign in with a passkey' ?></h2>
<p class="hint"><?= $signed_in ? 'Register a passkey on this phone. Android will ask you to approve with your fingerprint, face or screen lock. Your portal password remains available as a backup.' : 'Use a passkey you have already registered. Your phone may use a fingerprint or screen lock to approve.' ?></p>
<?php if ($signed_in): ?>
<label for="passkey-password">Confirm your portal password</label><input id="passkey-password" type="password" autocomplete="current-password" aria-describedby="passkey-status">
<?php endif; ?>
<p><button class="primary" type="button" id="passkey-button" data-mode="<?= $signed_in ? 'register' : 'login' ?>"><?= $signed_in ? 'Enable passkey on this phone' : 'Sign in with passkey' ?></button></p>
<p class="hint" id="passkey-status" role="status" aria-live="polite">Open this page in Chrome on your phone. Biometric approval stays on your device.</p>
<?php if ($signed_in): ?><p class="hint"><?= count(ppdp_passkey_records($key)) ?> passkey(s) registered. To revoke all passkeys, change the portal password in WordPress Settings → Puzzle Path Portal.</p><?php endif; ?>
<?php if ($signed_in): ?></section><?php else: ?></details><?php endif; ?>
