<?php

/**
 * Plugin Name: CC SEO Enhancer
 * Description: Advanced SEO helpers for meta tags, Open Graph, schema.org, robots, webmaster verification, and tracking integrations.
 * Version: 1.0.0
 * Author: Chance Lin
 * Author URI: https://cclin.cc
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cc-seo-enhancer
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CC_SEO_ENHANCER_VERSION', '1.0.0');
define('CC_SEO_ENHANCER_FILE', __FILE__);
define('CC_SEO_ENHANCER_PATH', plugin_dir_path(__FILE__));
define('CC_SEO_ENHANCER_URL', plugin_dir_url(__FILE__));

// Load plugin modules
require_once CC_SEO_ENHANCER_PATH . 'includes/settings.php';
require_once CC_SEO_ENHANCER_PATH . 'includes/seo-basic.php';
require_once CC_SEO_ENHANCER_PATH . 'includes/seo-cookie.php';
require_once CC_SEO_ENHANCER_PATH . 'includes/seo-wordcount.php';
require_once CC_SEO_ENHANCER_PATH . 'includes/seo-tracking.php';
require_once CC_SEO_ENHANCER_PATH . 'includes/seo-schema.php';
require_once CC_SEO_ENHANCER_PATH . 'includes/seo-robots.php';
require_once CC_SEO_ENHANCER_PATH . 'includes/seo-social.php';

// Enqueue admin scripts on the settings page
add_action('admin_enqueue_scripts', function ($hook) {

    // if ($hook !== 'settings_page_cc-seo-enhancer') return; // Use the actual settings page hook when limiting assets.
    wp_enqueue_script(
        'seo-enhancer-admin-js',
        CC_SEO_ENHANCER_URL . 'assets/js/seo-enhancer-admin.js',
        ['jquery'],
        CC_SEO_ENHANCER_VERSION,
        true
    );

    wp_enqueue_media();
    wp_enqueue_script('seo-enhancer-image', CC_SEO_ENHANCER_URL . 'assets/js/seo-image.js', ['jquery'], CC_SEO_ENHANCER_VERSION, true);
    wp_localize_script(
        'seo-enhancer-image',
        'ccSeoEnhancerMedia',
        [
            'title'      => __('Select or upload image', 'cc-seo-enhancer'),
            'buttonText' => __('Use this image', 'cc-seo-enhancer'),
        ]
    );

    // Enqueue settings page CSS
    wp_enqueue_style(
        'seo-enhancer-admin-css',
        CC_SEO_ENHANCER_URL . 'assets/css/seo-enhancer-admin.css',
        [],
        CC_SEO_ENHANCER_VERSION
    );
    //}
});

add_action('admin_head', function () {
    echo '<style>.column-seo_lang_code, .column-seo_origin_post_id { width: 10% }</style>';
});
