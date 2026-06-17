<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!get_option('enable_open_graph')) {
    return;
}

add_action('wp_head', function () {
    global $post, $wp;
    $url = home_url(add_query_arg([], $wp->request));
    // if (function_exists('get_permalink') && $link = get_permalink()) {
    // $url = trailingslashit($link);
// } else {
//     //global $wp;
//     $url = trailingslashit(home_url(add_query_arg([], $wp->request)));
// }

    $site_name = get_bloginfo('name');
    $description = get_bloginfo('description');

    // 改進版：優先使用 excerpt，若無則取第一段純文字，避免截斷
    // --- 自動產生網頁 <meta name="description"> ---
    if (is_singular()) {
        // 單篇文章或單頁
        $title = get_the_title();
        if (has_excerpt()) {
            $description = get_the_excerpt();
        } else {
            $content = strip_shortcodes(strip_tags(get_the_content()));
            $content = preg_replace('/\s+/', ' ', $content);
            $description = mb_substr(trim($content), 0, 160, 'UTF-8');
        }

    } elseif (is_home() || is_front_page()) {
        // 首頁
        $title = get_bloginfo('name');
        $description = get_bloginfo('description');

    } elseif (is_category() || is_tag() || is_tax()) {
        // 分類 / 標籤 / 自訂分類頁
        $title = get_the_archive_title();
        $desc = get_the_archive_description();
        $description = $desc ? wp_strip_all_tags($desc) : get_bloginfo('description');

    } elseif (is_author()) {
        // 作者頁
        $author = get_queried_object();
        $title = sprintf(__('作者：%s', 'textdomain'), $author->display_name);
        $bio = get_the_author_meta('description', $author->ID);
        $description = $bio ? wp_strip_all_tags($bio) : sprintf(__('作者：%s 的文章列表', 'textdomain'), $author->display_name);


    } elseif (is_date()) {
        // 日期歸檔頁
        $title = get_the_archive_title();
        $description = sprintf(__('發佈於 %s 的所有文章', 'textdomain'), get_the_archive_title());

    } elseif (is_search()) {
        // 搜尋結果頁
        $title = sprintf(__('搜尋結果：%s', 'textdomain'), get_search_query());
        $description = sprintf(__('搜尋「%s」的結果', 'textdomain'), get_search_query());

    } elseif (is_404()) {
        // 404 頁
        $title = __('找不到頁面', 'textdomain');
        $description = __('找不到您要的頁面。', 'textdomain');

    } else {
        // 其他情境（fallback）
        $title = get_bloginfo('name');
        $description = get_bloginfo('description');
    }




// --- 中略：其它 meta ---
     $post_id = get_queried_object_id();
    // $image = '';

    // if (is_singular() && has_post_thumbnail($post_id)) {
    //     $image = get_the_post_thumbnail_url($post_id, 'full');
    // }

      $image = (is_singular() && has_post_thumbnail($post_id))
    ? get_the_post_thumbnail_url($post_id, 'full')
    : plugin_dir_url(__DIR__) . 'assets/default-og-image.jpg';

    // --- 0. hreflang（單語網站預設）---
    // 若未啟用多語外掛（例如 cc-multi-language），則輸出預設繁體中文 hreflang
    if (!is_plugin_active('cc-multi-language/cc-multi-language.php')) {
        echo '<link rel="alternate" hreflang="zh-Hant" href="' . esc_url($url) . '">' . "\n";
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($url) . '">' . "\n";
    }
    // --- 1. 基本 Meta ---
    echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta name="robots" content="index, follow, max-image-preview:large">' . "\n";
    echo '<meta property="og:locale" content="zh_TW">' . "\n"; // 新增：語系宣告
    echo '<meta http-equiv="content-language" content="zh-TW">' . "\n"; // 新增：語言屬性

    // --- 2. Open Graph ---
    echo '<meta property="og:type" content="' . (is_singular() ? 'article' : 'website') . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";
    //if ($image) {
        echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
    //}

    // --- 3. Twitter Card ---
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
    echo '<meta name="twitter:site" content="@cclin">' . "\n"; // 新增：官方帳號（可改）
    //if ($image) {
    echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
    //}

    // --- 4. Canonical（確保唯一正規連結）---
    echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";

}, 20);
