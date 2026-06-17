<?php

if (!defined('ABSPATH')) {
    exit;
}

add_filter('wp_robots', function ($robots) {
    if (is_tag()) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }

    return $robots;
});

//remove google search display blogname(site title)
add_filter('document_title_parts', function ($title) {
    unset($title['site']);
    return $title;
}, 20);

function cclin_get_default_og_image($seed = '') {
    $base_dir = plugin_dir_path(__DIR__) . 'assets/image/';
    $base_url = plugin_dir_url(__DIR__) . 'assets/image/';
    $images = [];

    // Scan default-1.png through default-5.png.
    for ($i = 1; $i <= 5; $i++) {
        $file = $base_dir . 'default-' . $i . '.png';

        if (file_exists($file)) {
            $images[] = $base_url . 'default-' . $i . '.png';
        }
    }

    // Use a stable seed so the same page keeps the same fallback image.
    if (!empty($images)) {
        $seed = $seed ?: home_url('/');
        $index = abs(crc32($seed)) % count($images);

        return $images[$index];
    }

    // Fallback: default.png.
    if (file_exists($base_dir . 'default.png')) {
        return $base_url . 'default.png';
    }

    // Return an empty string when no fallback image exists.
    return '';
}

function cclin_get_first_content_attachment_id($content) {
    if (empty($content) || !preg_match_all('/wp-image-([0-9]+)/', $content, $matches)) {
        return 0;
    }

    foreach ($matches[1] as $attachment_id) {
        $attachment_id = absint($attachment_id);

        if ($attachment_id && 'attachment' === get_post_type($attachment_id)) {
            return $attachment_id;
        }
    }

    return 0;
}

function cclin_generate_og_image_from_attachment($attachment_id) {
    $attachment_id = absint($attachment_id);

    if (!$attachment_id) {
        return '';
    }

    $source_path = get_attached_file($attachment_id);

    if (!$source_path || !file_exists($source_path)) {
        return '';
    }

    $metadata = wp_get_attachment_metadata($attachment_id);
    $width = isset($metadata['width']) ? absint($metadata['width']) : 0;
    $height = isset($metadata['height']) ? absint($metadata['height']) : 0;

    if ($width < 1200 || $height < 630) {
        return '';
    }

    $uploads = wp_upload_dir();

    if (!empty($uploads['error']) || empty($uploads['basedir']) || empty($uploads['baseurl'])) {
        return '';
    }

    $target_dir = trailingslashit($uploads['basedir']) . 'cc-seo-enhancer/og/';
    $target_url = trailingslashit($uploads['baseurl']) . 'cc-seo-enhancer/og/';

    if (!wp_mkdir_p($target_dir)) {
        return '';
    }

    $source_hash = md5($attachment_id . '|' . filemtime($source_path) . '|' . filesize($source_path));
    $target_file = 'attachment-' . $attachment_id . '-' . $source_hash . '.jpg';
    $target_path = $target_dir . $target_file;

    if (file_exists($target_path)) {
        return $target_url . $target_file;
    }

    $image_editor = wp_get_image_editor($source_path);

    if (is_wp_error($image_editor)) {
        return '';
    }

    if (method_exists($image_editor, 'set_quality')) {
        $image_editor->set_quality(90);
    }

    $resize_result = $image_editor->resize(1200, 630, true);

    if (is_wp_error($resize_result)) {
        return '';
    }

    $save_result = $image_editor->save($target_path, 'image/jpeg');

    if (is_wp_error($save_result) || !file_exists($target_path)) {
        return '';
    }

    return $target_url . $target_file;
}

function cclin_get_first_generated_og_image(array $attachment_ids) {
    foreach ($attachment_ids as $attachment_id) {
        $image_url = cclin_generate_og_image_from_attachment($attachment_id);

        if ($image_url) {
            return $image_url;
        }
    }

    return '';
}

add_action('wp_head', function () {
    global $post, $wp;

    $site_name = get_bloginfo('name');
    $type = 'website';
      if (is_singular('post')) {
          $type = 'article';
      } elseif (is_author()) {
          $type = 'profile';
      }
    $url = trailingslashit(home_url(add_query_arg([], $wp->request ?? '')));
    $title = get_bloginfo('name');
    $description = get_bloginfo('description');
    $image = '';
    $author = '';
    $published_time = '';
    $modified_time = '';

    if (is_singular()) {
        // Singular posts and pages.
        $url   = trailingslashit(get_permalink());
        $title = get_the_title();
        $published_time = get_the_date('c', $post->ID);      // ISO 8601 format.
        $modified_time  = get_the_modified_date('c', $post->ID);
        $author_obj     = get_userdata($post->post_author);
        $author         = $author_obj ? $author_obj->display_name : '';

        $image_attachment_ids = [];
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $content_attachment_id = cclin_get_first_content_attachment_id($post->post_content);

        if ($thumbnail_id) {
            $image_attachment_ids[] = $thumbnail_id;
        }

        if ($content_attachment_id && $content_attachment_id !== $thumbnail_id) {
            $image_attachment_ids[] = $content_attachment_id;
        }

        $image = cclin_get_first_generated_og_image($image_attachment_ids);

        if (has_excerpt()) {
            $description = get_the_excerpt();
        } else {
            $content = strip_shortcodes(wp_strip_all_tags(get_the_content()));
            $description = mb_substr(trim(preg_replace('/\s+/', ' ', $content)), 0, 160, 'UTF-8');
        }

    } elseif (is_category() || is_tag() || is_tax()) {
        // Category, tag, and custom taxonomy archives.
        $term = get_queried_object();
        $url  = trailingslashit(get_term_link($term));
        $title = wp_strip_all_tags(get_the_archive_title());
        $desc  = get_the_archive_description();
        $description = $desc ? wp_strip_all_tags($desc) : get_bloginfo('description');

        // Image priority: term image, then the newest post with a featured image.
        $thumb_id = get_term_meta($term->term_id, 'thumbnail_id', true);
        if ($thumb_id) {
            $image = cclin_generate_og_image_from_attachment($thumb_id);
        } else {
            $post_ids = get_posts([
                'posts_per_page' => 20,
                'post_type'      => 'post',
                'orderby'        => 'date',
                'order'          => 'DESC',
                'fields'         => 'ids',
                'tax_query'      => [[
                    'taxonomy' => $term->taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $term->term_id,
                ]],
            ]);
            foreach ($post_ids as $pid) {
                $thumbnail_id = get_post_thumbnail_id($pid);

                if ($thumbnail_id) {
                    $image = cclin_generate_og_image_from_attachment($thumbnail_id);

                    if (!$image) {
                        continue;
                    }

                    break;
                }
            }
        }

    } elseif (is_home() || is_front_page()) {
        // Home page.
        $url   = trailingslashit(home_url('/'));
        $title = get_bloginfo('name');
        $description = get_bloginfo('description');
    } elseif (is_search()) {
        // Search results page.
        $url   = trailingslashit(get_search_link());
        /* translators: %s: Search query. */
        $title = sprintf(__('Search results: %s', 'cc-seo-enhancer'), get_search_query());
        /* translators: %s: Search query. */
        $description = sprintf(__('Search results for "%s"', 'cc-seo-enhancer'), get_search_query());

    } elseif (is_author()) {
        // Author archive.
        $author = get_queried_object();
        $url   = trailingslashit(get_author_posts_url($author->ID));
        /* translators: %s: Author display name. */
        $title = sprintf(__('Author: %s', 'cc-seo-enhancer'), $author->display_name);
        $description = get_the_author_meta('description', $author->ID) ?: get_bloginfo('description');

    } elseif (is_404()) {
        // 404 page.
        $url   = home_url('/');
        $title = __('Page not found', 'cc-seo-enhancer');
        $description = __('Sorry, the page you requested could not be found.', 'cc-seo-enhancer');
    }

    if (empty($image)) {
        $image = cclin_get_default_og_image($url);
    }

    // --- 1. Basic meta tags ---
    echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";

    echo '<meta property="og:locale" content="zh_TW" />' . "\n"; // Locale declaration.
    echo '<meta http-equiv="content-language" content="zh-TW" />' . "\n"; // Content language declaration.

    // --- 2. Open Graph ---
    echo '<meta property="og:type" content="' . esc_attr($type) . '" />' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />' . "\n";
    echo '<meta property="og:image:width" content="1200" />' . "\n";
    echo '<meta property="og:image:height" content="630" />' . "\n";

    // --- Detect the image MIME type dynamically. ---
    $filetype = wp_check_filetype($image);
    if (!empty($filetype['type'])) {
        echo '<meta property="og:image:type" content="' . esc_attr($filetype['type']) . '" />' . "\n";
    }
    if (!empty($image)) {
        echo '<meta property="og:image" content="' . esc_url($image) . '" />' . "\n";
    }

    if (is_singular()) {
        echo '<meta property="article:published_time" content="' . esc_attr($published_time) . '" />' . "\n";
        echo '<meta property="article:modified_time" content="' . esc_attr($modified_time) . '" />' . "\n";

    }

    // --- 3. Twitter Card ---
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";
    if (!empty($image)) {
        echo '<meta name="twitter:image" content="' . esc_url($image) . '" />' . "\n";
    }
    // echo '<meta name="twitter:site" content="@cclin">' . "\n"; // Optional official account.

    // --- Supplemental label/data pairs ---
    if (is_singular()) {
        // Estimate reading time with a simple average words-per-minute model.
        $word_count    = cclin_seo_wordcount(wp_strip_all_tags($post->post_content));
        $reading_time = ceil($word_count / 400);
        /* translators: %d: Estimated reading time in minutes. */
        $reading_time_text = sprintf(_n('%d minute', '%d minutes', $reading_time, 'cc-seo-enhancer'), $reading_time);
        echo '<meta name="twitter:label1" content="' . esc_attr__('Author', 'cc-seo-enhancer') . '" />' . "\n";
        echo '<meta name="twitter:data1" content="' . esc_attr($author) . '" />' . "\n";
        echo '<meta name="twitter:label2" content="' . esc_attr__('Estimated reading time', 'cc-seo-enhancer') . '" />' . "\n";
        echo '<meta name="twitter:data2" content="' . esc_attr($reading_time_text) . '" />' . "\n";
    }

    // --- 4. Canonical URL ---
    //echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";

}, 20);
