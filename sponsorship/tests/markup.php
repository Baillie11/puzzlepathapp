<?php
ob_start(); require __DIR__ . '/preview.php'; $html = ob_get_clean();
$dom = new DOMDocument(); libxml_use_internal_errors(true); $dom->loadHTML($html); libxml_clear_errors(); $xpath = new DOMXPath($dom);
$checks = 0;
function verify_markup($condition, $label) { global $checks; if (!$condition) throw new Exception($label); $checks++; }
verify_markup($xpath->query('//h1')->length === 1, 'Exactly one H1');
verify_markup($xpath->query('//details')->length === 9, 'Nine FAQs');
verify_markup($xpath->query('//h2[@id="pps-final"]')->length === 1, 'Final CTA renders');
verify_markup($xpath->query('//a[@href="#sponsorship-form"]')->length === 2, 'Two form CTAs');
verify_markup($xpath->query('//a[@href="https://puzzlepath.com.au/puzzle-path-upcoming-adventures/"]')->length === 2, 'Existing adventure links');
$ids = []; foreach ($xpath->query('//*[@id]') as $el) { verify_markup(!isset($ids[$el->getAttribute('id')]), 'No duplicate ID'); $ids[$el->getAttribute('id')] = true; }
foreach ($xpath->query('//a[starts-with(@href,"#")]') as $el) verify_markup(isset($ids[substr($el->getAttribute('href'), 1)]), 'Anchor target exists');
foreach ($xpath->query('//label[@for]') as $el) verify_markup(isset($ids[$el->getAttribute('for')]), 'Label target exists');
foreach ($xpath->query('//img') as $el) verify_markup($el->getAttribute('alt') !== '', 'Image alt text exists');
verify_markup($xpath->query('//input[@name="phone" and @disabled]')->length === 1, 'Phone starts disabled');
verify_markup($xpath->query('//input[@name="contact_method" and @value="email" and @checked]')->length === 1, 'Email is default');
echo "PASS: $checks rendered markup checks.\n";
