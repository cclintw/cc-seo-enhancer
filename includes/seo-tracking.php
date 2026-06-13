<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('seo_enhancer_enabled')) {
    function seo_enhancer_enabled($key)
    {
        return (int) get_option($key) === 1;
    }
}

if (!function_exists('cc_seo_enhancer_normalize_path')) {
    function cc_seo_enhancer_normalize_path($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        $parsed_path = wp_parse_url($path, PHP_URL_PATH);
        $path = $parsed_path ?: $path;
        $path = '/' . ltrim($path, '/');
        $path = preg_replace('#/+#', '/', $path);

        if ($path !== '/') {
            $path = untrailingslashit($path);
        }

        return $path;
    }
}

if (!function_exists('cc_seo_enhancer_sanitize_ga4_event_name')) {
    function cc_seo_enhancer_sanitize_ga4_event_name($event_name)
    {
        $event_name = strtolower(trim((string) $event_name));
        $event_name = preg_replace('/[^a-z0-9_]/', '_', $event_name);
        $event_name = preg_replace('/_+/', '_', $event_name);
        $event_name = trim($event_name, '_');

        return $event_name ?: 'conversion';
    }
}

// 1. Google Tag Manager
add_action('wp_head', function () {
    $gtm_id = get_option('seo_enhancer_gtm_id');
    if (seo_enhancer_enabled('enable_gtm_tracking') && $gtm_id && preg_match('/^GTM-[A-Z0-9]+$/', $gtm_id)) {
        echo "<!-- Google Tag Manager -->\n";
        echo "<script>
(function(w,d,s,l,i){
  w[l]=w[l]||[];
  w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
  var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
  j.async=true;
  j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
  f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','" . esc_js($gtm_id) . "');
</script>\n";
    }
}, 20);

add_action('wp_body_open', function () {
    $gtm_id = get_option('seo_enhancer_gtm_id');
    if (seo_enhancer_enabled('enable_gtm_tracking') && $gtm_id && preg_match('/^GTM-[A-Z0-9]+$/', $gtm_id)) {
        echo "<!-- Google Tag Manager (noscript) -->\n";
        echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr($gtm_id) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n";
        echo "<!-- End Google Tag Manager (noscript) -->\n";
    }
}, 1);


// 2. Google Analytics (GA4)
add_action('wp_head', function () {
    $ga_id = get_option('seo_enhancer_ga_id');
    if (seo_enhancer_enabled('enable_ga_tracking') && $ga_id && preg_match('/^G-[A-Z0-9]+$/', $ga_id)) {
        echo "<!-- Google Analytics -->\n";
        echo "<script>
var gtagScript = document.createElement('script');
gtagScript.async = true;
gtagScript.src = 'https://www.googletagmanager.com/gtag/js?id=" . esc_js(rawurlencode($ga_id)) . "';
document.head.appendChild(gtagScript);
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '" . esc_js($ga_id) . "');
</script>\n";
    }
}, 20);


// 3. Facebook Pixel
add_action('wp_head', function () {
    $fb_id = get_option('seo_enhancer_fb_pixel_id');
    if (seo_enhancer_enabled('enable_fb_pixel') && $fb_id && preg_match('/^[0-9]{10,20}$/', $fb_id)) {
        echo "<!-- Facebook Pixel -->\n";
        echo "<script>
!function(f,b,e,v,n,t,s){
    if(f.fbq) return;
    n=f.fbq=function(){n.callMethod ?
      n.callMethod.apply(n,arguments) : n.queue.push(arguments)};
    if(!f._fbq) f._fbq=n;
    n.push=n; n.loaded=!0; n.version='2.0';
    n.queue=[]; t=b.createElement(e); t.async=!0;
    t.src=v; s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)
}(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');

fbq('init', '" . esc_js($fb_id) . "');
fbq('track', 'PageView');
</script>\n";
        echo "<noscript><img height='1' width='1' style='display:none' src='https://www.facebook.com/tr?id=" . esc_attr($fb_id) . "&ev=PageView&noscript=1'/></noscript>\n";
    }
}, 20);


//4.google verification
add_action('wp_head', function () {
    $verification = trim(get_option('seo_enhancer_google_verification'));
    if ($verification) {
        echo "\n<meta name=\"google-site-verification\" content=\"" . esc_attr($verification) . "\">\n";
    }
}, 10);


//5.bing verification
add_action('wp_head', function () {
    $bing_verification = trim(get_option('seo_enhancer_bing_verification'));
    if ($bing_verification) {
        echo '<meta name="msvalidate.01" content="' . esc_attr($bing_verification) . '">' . "\n";
    }
}, 10);

// 8. Automatic conversion events with per-path event names.
add_action('wp_footer', function () {
    if (!seo_enhancer_enabled('enable_conversion_tracking')) {
        return;
    }

    $ga_id = get_option('seo_enhancer_ga_id');
    if (!seo_enhancer_enabled('enable_ga_tracking') || !$ga_id || !preg_match('/^G-[A-Z0-9]+$/', $ga_id)) {
        return;
    }

    $conversion_raw = get_option('seo_enhancer_conversion_paths');

    if (empty($conversion_raw)) return;

    // Parse into URL => event_name mappings.
    $lines = array_filter(array_map('trim', explode("\n", $conversion_raw)));
    $map = [];
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($url, $event) = array_map('trim', explode('=', $line, 2));
            $path = cc_seo_enhancer_normalize_path($url);
            if ($path !== '') {
                $map[$path] = cc_seo_enhancer_sanitize_ga4_event_name($event);
            }
        } else {
            // Default to conversion when no event name is specified.
            $path = cc_seo_enhancer_normalize_path($line);
            if ($path !== '') {
                $map[$path] = 'conversion';
            }
        }
    }

    // Check whether the current path matches.
    $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
    $path = cc_seo_enhancer_normalize_path($request_uri);
    if (!isset($map[$path])) return;

    $event_name = $map[$path];
    ?>
<!-- Conversion Tracking -->
<script>
	if (typeof gtag === 'function') {
		gtag('event', '<?php echo esc_js($event_name); ?>', {
			send_to: '<?php echo esc_js($ga_id); ?>'
		});
	}
</script>
<?php
}, 100);
?>
