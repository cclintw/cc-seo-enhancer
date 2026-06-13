<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('cclin_seo_wordcount')) {
    function cclin_seo_wordcount($text) {
        // Ensure the input is treated as a string.
        $text = (string) $text;
        if ($text === '') return 0;

        // Remove HTML, shortcodes, entities, and extra whitespace.
        $text = strip_shortcodes($text);
        $text = wp_strip_all_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);

        // Count by token:
        //  - \p{Han}: count each CJK ideograph as one token.
        //  - [A-Za-z]+: count each Latin word sequence as one token.
        //  - \d+: count each number sequence as one token.
        //  - [^\s\p{Han}]: count each other non-space symbol as one token.
        $pattern = '/\p{Han}|[A-Za-z]+|\d+|[^\s\p{Han}]/u';
        if (preg_match_all($pattern, $text, $m)) {
            return count($m[0]);
        }
        return 0;
    }
}
