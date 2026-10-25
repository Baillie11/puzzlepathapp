<?php
if (!defined('ABSPATH') && !defined('PP_PARTNER_PREVIEW')) { exit; }
$preview = defined('PP_PARTNER_PREVIEW');
$asset = $preview ? 'puzzlepath-partnership/assets/' : plugin_dir_url(__FILE__) . 'assets/';
$status = !$preview && isset($_GET['partner_status']) ? sanitize_key($_GET['partner_status']) : '';
?>
<div class="pp-partner">
  <section class="pp-hero" aria-labelledby="pp-title">
    <div class="pp-hero-copy">
      <p class="pp-eyebrow">LOCAL BUSINESSES. SHARED ADVENTURES.</p>
      <h1 id="pp-title">Become Part<br>of the Adventure!</h1>
      <p class="pp-lead">Partner with Puzzle Path Scavenger Hunts</p>
      <p>Help visitors and locals discover your business through fun, interactive scavenger hunts across Queensland.</p>
      <a class="pp-button" href="#partner-register">Let’s partner up <span aria-hidden="true">↗</span></a>
      <p class="pp-free">No joining fees. No participation fees.<br>Just opportunities to be discovered.</p>
    </div>
    <div class="pp-hero-art"><div class="pp-island" aria-hidden="true"></div><img src="<?= htmlspecialchars($asset) ?>koko-transparent.png" alt="Koko the koala explorer, holding a magnifying glass and adventure map" width="1024" height="1536" fetchpriority="high"><span class="pp-art-note">Let’s discover<br>something local!</span></div>
  </section>
  <section class="pp-how" aria-labelledby="pp-how-title">
    <div class="pp-section-head"><p class="pp-eyebrow">A LITTLE CURIOSITY GOES A LONG WAY</p><h2 id="pp-how-title">How the partnership works</h2></div>
    <ol class="pp-steps">
      <li><span>01</span><div><h3>Introduce your business</h3><p>Tell us where you are and how you’d like to be involved. It’s free to express interest.</p></div></li>
      <li><span>02</span><div><h3>Explore the possibilities</h3><p>We’ll review your enquiry and get in touch to discuss whether there’s a suitable opportunity.</p></div></li>
      <li><span>03</span><div><h3>Plan something together</h3><p>If there’s a good fit, we’ll agree on the details before including your business in an adventure.</p></div></li>
    </ol>
    <p class="pp-note">Every hunt is thoughtfully planned. Registering your interest doesn’t guarantee inclusion in a hunt.</p>
  </section>
  <section class="pp-benefits" aria-labelledby="pp-benefit-title">
    <div class="pp-section-head"><p class="pp-eyebrow">GOOD THINGS HAPPEN LOCALLY</p><h2 id="pp-benefit-title">Why partner with Puzzle Path?</h2></div>
    <div class="pp-cards">
      <article><span class="pp-icon" aria-hidden="true">⌕</span><h3>Get discovered</h3><p>Introduce your business to locals, families and visitors exploring your area.</p></article>
      <article><span class="pp-icon" aria-hidden="true">✧</span><h3>Be part of the fun</h3><p>Your business could become a clue, checkpoint or memorable discovery.</p></article>
      <article><span class="pp-icon" aria-hidden="true">✓</span><h3>Free to participate</h3><p>No joining fees or mandatory purchases.</p></article>
      <article><span class="pp-icon" aria-hidden="true">♡</span><h3>Support local</h3><p>Help showcase the places, people and businesses that make Queensland special.</p></article>
    </div>
  </section>
  <section class="pp-register" id="partner-register" aria-labelledby="pp-register-title">
    <aside class="pp-register-intro"><p class="pp-eyebrow">YOUR NEXT CHAPTER STARTS HERE</p><h2 id="pp-register-title">Partner with<br>Puzzle Path</h2><p>Tell us a little about your business and how you’d like to get involved.</p><span class="pp-badge">Free expression of interest</span><div class="pp-aside-note"><h3>Big ideas. Little discoveries.</h3><p>From a neighbourhood café to a community organisation, we’d love to hear what makes your place special.</p><p>Have a question first?<br><a href="mailto:info@puzzlepath.com.au">info@puzzlepath.com.au</a></p></div></aside>
    <div class="pp-form-card">
    <?php if ($status === 'sent'): ?><div class="pp-result" role="status" tabindex="-1"><h3>Thanks for your interest!</h3><p>Your enquiry has been sent to Puzzle Path. We’ll review the details and get in touch about possible opportunities.</p></div><?php endif; ?>
    <?php if ($status === 'error'): ?><div class="pp-result pp-error" role="alert"><h3>We couldn’t send your enquiry.</h3><p>Please try again, or email <a href="mailto:info@puzzlepath.com.au">info@puzzlepath.com.au</a>.</p></div><?php endif; ?>
    <?php if ($preview): ?><p class="pp-preview-note">Local preview: sending is disabled here. The WordPress form sends enquiries to info@puzzlepath.com.au.</p><?php endif; ?>
    <form id="pp-partner-form" method="post" action="<?= $preview ? '#' : esc_url(admin_url('admin-post.php')) ?>" data-preview="<?= $preview ? 'true' : 'false' ?>">
      <input type="hidden" name="action" value="pp_partner_enquiry">
      <?php if (!$preview) { wp_nonce_field('pp_partner_enquiry', 'pp_partner_nonce'); } ?>
      <div class="pp-hp" aria-hidden="true"><label>Leave this field empty<input name="company_fax" tabindex="-1" autocomplete="off"></label></div>
      <p class="pp-required">Fields marked * are required.</p>
      <fieldset><legend><span>01</span> About your business</legend>
        <label for="pp-business">Business / Organisation Name *</label><input id="pp-business" name="business" required maxlength="200" autocomplete="organization">
        <label for="pp-website">Website or Social Media Page</label><input id="pp-website" name="website" type="text" inputmode="url" maxlength="500" placeholder="e.g. your website or Instagram page">
        <label for="pp-location">Business Address / Location *</label><input id="pp-location" name="location" required maxlength="500" aria-describedby="pp-location-help" autocomplete="street-address"><small id="pp-location-help">Where could participants discover your business?</small>
        <label for="pp-type">What type of business or organisation are you?</label><select id="pp-type" name="business_type"><option value="">Select a category</option><option>Retail / Shopping</option><option>Cafe / Restaurant / Food</option><option>Tourism / Attractions / Entertainment</option><option>Community / Not-for-profit</option><option>Other</option></select>
      </fieldset>
      <fieldset><legend><span>02</span> Contact details</legend>
        <label for="pp-name">Contact Person’s Name *</label><input id="pp-name" name="contact_name" required maxlength="200" autocomplete="name">
        <div class="pp-field-grid"><div><label for="pp-email">Email Address *</label><input id="pp-email" name="email" type="email" required maxlength="254" autocomplete="email"></div><div><label for="pp-phone">Contact Phone Number</label><input id="pp-phone" name="phone" type="tel" maxlength="60" autocomplete="tel"></div></div>
        <fieldset class="pp-choice-group"><legend>Preferred Contact Method</legend><div class="pp-inline"><label><input type="radio" name="contact_method" value="Email" checked> Email</label><label><input type="radio" name="contact_method" value="Phone"> Phone</label></div></fieldset>
      </fieldset>
      <fieldset><legend><span>03</span> Let’s create an adventure!</legend>
        <fieldset class="pp-choice-group"><legend>How would you like to be involved?</legend><small>Select any that interest you.</small>
        <?php foreach (['Feature my business as a scavenger hunt checkpoint', 'Have a clue or puzzle based on my business', 'Provide an optional special offer or reward', 'Explore a customised scavenger hunt', 'I’m open to suggestions'] as $option): ?><label class="pp-check"><input type="checkbox" name="involvement[]" value="<?= htmlspecialchars($option) ?>"> <span><?= htmlspecialchars($option) ?></span></label><?php endforeach; ?>
        </fieldset>
        <fieldset class="pp-choice-group"><legend>Would you consider offering participants a small discount, special offer or reward?</legend>
        <?php foreach (['Yes, I’d be interested', 'Maybe, let’s discuss', 'No, not at this stage'] as $option): ?><label class="pp-check"><input type="radio" name="offer" value="<?= htmlspecialchars($option) ?>"> <span><?= htmlspecialchars($option) ?></span></label><?php endforeach; ?>
        <small>Completely optional. You don’t need to offer anything to be considered.</small></fieldset>
        <label for="pp-notes">Anything else you’d like us to know?</label><textarea id="pp-notes" name="notes" rows="4" maxlength="4000"></textarea>
      </fieldset>
      <label class="pp-check pp-consent"><input type="checkbox" name="consent" value="yes" required><span>I agree to be contacted by Puzzle Path about my partnership enquiry. *</span></label>
      <p class="pp-privacy">We’ll use the details you provide to respond to this partnership enquiry.</p>
      <button class="pp-button" type="submit"><?= $preview ? 'Preview enquiry' : 'Send partnership enquiry' ?> <span aria-hidden="true">↗</span></button>
      <p class="pp-form-status" role="status" aria-live="polite"></p>
      <p class="pp-small-print">An expression of interest is the first step, not a confirmed partnership. Inclusion depends on suitability for our hunts.</p>
    </form></div>
  </section>
</div>
