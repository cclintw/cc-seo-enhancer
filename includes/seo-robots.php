<?php
if (!defined('ABSPATH')) exit;
// ========================================
// Dynamic robots.txt output.
// ========================================

add_filter('robots_txt', 'cc_seo_filter_robots_txt', 10, 2);

function cc_seo_filter_robots_txt($output, $public) {
    return get_robots_txt_content($public);
}

// ========================================
// Generate robots.txt content.
// ========================================
function get_robots_txt_content($public = null) {
    if ($public === null) {
        $public = get_option('blog_public', '1');
    }

    $lines = [];
    $lines[] = 'User-agent: *';  // Apply rules to all crawlers.

    if ((string) $public === '0') {
        // Block all crawlers when WordPress discourages search engines.
        $lines[] = 'Disallow: /';  // Disallow the entire site.
    } else {
        // Public site defaults: restrict admin and login paths.
        $lines[] = 'Disallow: /wp-admin/';
        $lines[] = 'Disallow: /wp-login.php';
        $lines[] = 'Disallow: /wp-register.php';       
        // Allow admin-ajax.php for frontend AJAX requests.
        $lines[] = 'Allow: /wp-admin/admin-ajax.php';
    }

    // Add custom Disallow paths.
    $extra_paths = get_option('seo_enhancer_disallow_paths');
    if (!empty($extra_paths)) {
        foreach (explode("\n", $extra_paths) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $path = '/' . ltrim($line, '/');
                $lines[] = "Disallow: {$path}";
            }
        }
    }

    // Sitemap
    $lines[] = 'Sitemap: ' . home_url('/wp-sitemap.xml');

    return implode("\n", $lines) . "\n";
}
