<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!get_option('enable_open_graph')) {
    return;
}
function cclin_get_random_default_og_image() {

    $base_dir = plugin_dir_path(__DIR__) . 'assets/image/';
    $base_url = plugin_dir_url(__DIR__) . 'assets/image/';

    $images = [];

    // 掃描 default-1.png ~ default-5.png
    for ($i = 1; $i <= 5; $i++) {

        $file = $base_dir . 'default-' . $i . '.png';

        if (file_exists($file)) {
            $images[] = $base_url . 'default-' . $i . '.png';
        }
    }

    // 有可用圖片則隨機回傳
    if (!empty($images)) {
        return $images[array_rand($images)];
    }

    // fallback: default.png
    if (file_exists($base_dir . 'default.png')) {
        return $base_url . 'default.png';
    }

    // 完全沒圖則回傳空字串
    return '';
}

add_action('wp_head', function () {
    global $post, $wp;

    $site_name   = get_bloginfo('name');
    $type = (is_home() || is_front_page()) ? 'website' : 'article';


    if (is_singular()) {
        // 單篇文章或頁面
        $url   = trailingslashit(get_permalink());
        $title = get_the_title();
        $published_time = get_the_date('c', $post->ID);      // ISO 8601 格式（推薦）
        $modified_time  = get_the_modified_date('c', $post->ID);
        $author_obj     = get_userdata($post->post_author);
        $author         = $author_obj ? $author_obj->display_name : '';

        if (has_post_thumbnail($post->ID)) {
            $image = get_the_post_thumbnail_url($post->ID, 'full');
        }

        if (has_excerpt()) {
            $description = get_the_excerpt();
        } else {
            $content = strip_shortcodes(strip_tags(get_the_content()));
            $description = mb_substr(trim(preg_replace('/\s+/', ' ', $content)), 0, 160, 'UTF-8');
        }

    } elseif (is_category() || is_tag() || is_tax()) {
        // 分類、標籤、自訂分類頁
        $term = get_queried_object();
        $url  = trailingslashit(get_term_link($term));
        $title = strip_tags(get_the_archive_title());
        $desc  = get_the_archive_description();
        $description = $desc ? wp_strip_all_tags($desc) : get_bloginfo('description');

        // 圖片：先抓分類圖 → 再抓分類下第一篇有精選圖的文章
        $thumb_id = get_term_meta($term->term_id, 'thumbnail_id', true);
        if ($thumb_id) {
            $image = wp_get_attachment_url($thumb_id);
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
                if (has_post_thumbnail($pid)) {
                    $image = get_the_post_thumbnail_url($pid, 'full');
                    break;
                }
            }
        }

        } elseif (is_home() || is_front_page()) {
        // 首頁
        $url   = trailingslashit(home_url('/'));
        $title = get_bloginfo('name');
        $description = get_bloginfo('description');
        $image = cclin_get_random_default_og_image();
    } elseif (is_search()) {
        // 搜尋結果頁
        $url   = trailingslashit(get_search_link());
        $title = sprintf(__('搜尋結果：%s', 'textdomain'), get_search_query());
        $description = sprintf(__('搜尋「%s」的結果', 'textdomain'), get_search_query());

    } elseif (is_author()) {
        // 作者頁
        $author = get_queried_object();
        $url   = trailingslashit(get_author_posts_url($author->ID));
        $title = sprintf(__('作者：%s', 'textdomain'), $author->display_name);
        $description = get_the_author_meta('description', $author->ID) ?: get_bloginfo('description');

    } elseif (is_404()) {
        // 404 頁
        $url   = home_url('/');
        $title = __('找不到頁面', 'default');
        $description = __('抱歉，找不到您要的頁面。', 'default');
    }

       if (empty($image)) {
        $image = cclin_get_random_default_og_image();
    }

    // --- 0. hreflang（單語網站預設）---
    // 若未啟用多語外掛（例如 cc-multi-language），則輸出預設繁體中文 hreflang
    if (!is_plugin_active('cc-multi-language/cc-multi-language.php')) {
        echo '<link rel="alternate" hreflang="zh-Hant" href="' . esc_url($url) . '" />' . "\n";
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($url) . '" />' . "\n";
    }
    // --- 1. 基本 Meta ---
    echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";

    if ( is_tag() ) {
        echo '<meta name="robots" content="noindex,follow,max-image-preview:large" />' . "\n";
    }


    echo '<meta property="og:locale" content="zh_TW" />' . "\n"; // 新增：語系宣告
    echo '<meta http-equiv="content-language" content="zh-TW" />' . "\n"; // 新增：語言屬性

    // --- 2. Open Graph ---
    echo '<meta property="og:type" content="' . esc_attr($type) . '" />' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />' . "\n";
    echo '<meta property="og:image:width" content="1200" />' . "\n";
    echo '<meta property="og:image:height" content="630" />' . "\n";

    // --- 動態偵測圖片 MIME type ---
    $filetype = wp_check_filetype( $image );
    if ( ! empty( $filetype['type'] ) ) {
        echo '<meta property="og:image:type" content="' . esc_attr( $filetype['type'] ) . '" />' . "\n";
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
    echo '<meta name="twitter:image" content="' . esc_url($image) . '" />' . "\n";
    //echo '<meta name="twitter:site" content="@cclin">' . "\n"; // 新增：官方帳號（可改）

    // --- 補充欄位（Label / Data pairs）---
    if (is_singular()) {
        // 預估閱讀時間（簡易計算：假設平均200字/分鐘）
        $word_count    = cclin_seo_wordcount(strip_tags($post->post_content));
        $reading_time = ceil($word_count / 400);
        $reading_time_text = $reading_time . ' 分鐘';
        echo '<meta name="twitter:label1" content="作者" />' . "\n";
        echo '<meta name="twitter:data1" content="' . esc_attr($author) . '" />' . "\n";
        echo '<meta name="twitter:label2" content="預估閱讀時間" />' . "\n";
        echo '<meta name="twitter:data2" content="' . esc_attr($reading_time_text) . '" />' . "\n";
    }

    // --- 4. Canonical（確保唯一正規連結）---
    echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";

}, 20);
