<?php
defined('ABSPATH') || exit;
// This site emits a hard-coded theme SEO block and a second Click SEO title.
// Normalise only this page's head; other pages and scripts are left untouched.
function pps_normalise_head(string $html, string $metadata): string {
    return preg_replace_callback('~(<head\b[^>]*>)(.*?)(</head>)~is', function ($match) use ($metadata) {
        $head = preg_replace('~<title\b[^>]*>.*?</title>~is', '', $match[2]);
        $head = preg_replace_callback('~<meta\b[^>]*>~i', function ($tag) {
            if (preg_match('~\b(?:name|property)\s*=\s*([\'"])(description|og:title|og:description|og:image|og:image:alt|og:type|og:url|twitter:title|twitter:description|twitter:image)\1~i', $tag[0])) return '';
            return $tag[0];
        }, $head);
        return $match[1] . $metadata . $head . $match[3];
    }, $html, 1);
}
add_action('template_redirect', function () {
    if (!pps_is_page() || is_feed()) return;
    $title = 'Community Sponsorship | Puzzle Path Scavenger Hunts';
    $description = pps_description();
    $image = plugins_url('assets/koko-transparent.png', __FILE__);
    $meta = '<title>' . esc_html($title) . '</title><meta name="description" content="' . esc_attr($description) . '">';
    foreach (['og:title' => $title, 'og:description' => $description, 'og:image' => $image, 'og:image:alt' => 'Koko holding a magnifying glass and treasure map', 'og:type' => 'website', 'og:url' => get_permalink()] as $key => $value) $meta .= '<meta property="' . esc_attr($key) . '" content="' . esc_attr($value) . '">';
    foreach (['twitter:title' => $title, 'twitter:description' => $description, 'twitter:image' => $image] as $key => $value) $meta .= '<meta name="' . esc_attr($key) . '" content="' . esc_attr($value) . '">';
    ob_start(fn($html) => pps_normalise_head($html, $meta));
}, 99);
