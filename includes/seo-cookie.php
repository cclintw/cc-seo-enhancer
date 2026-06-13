<?php
if (!defined('ABSPATH')) {
    exit;
}

// Output the frontend cookie notice banner and controller script.
add_action('wp_footer', function () {

    if (!get_option('enable_cookie_consent')) {
        return;
    } // Stop when the cookie notice is disabled.

    ?>
<div id="cookie-notice-banner"
	style="position: fixed; bottom: 0; left: 0; right: 0; background: #333; color: #fff; padding: 15px; z-index:9999; display: none; text-align: center;">
	<!-- Close button -->
	<span id="close-cookie-notice"
		style="position: absolute; top: 5px; right: 10px; cursor: pointer; font-size: 18px; color: #fff;">&times;</span>
	<?php esc_html_e('This website uses cookies and similar technologies to improve the experience and analyze site traffic.', 'cc-seo-enhancer'); ?>
	<button id="dismiss-cookie-notice"
		style="margin-left: 20px; background: #00cc66; color: #fff; padding: 6px 12px; border: none; cursor: pointer;"><?php esc_html_e('Got it', 'cc-seo-enhancer'); ?></button>
</div>
<script>
	(function() {
		const noticeKey = 'cc_cookie_notice_dismissed';
		const banner = document.getElementById('cookie-notice-banner');

		if (banner && !localStorage.getItem(noticeKey)) {
			banner.style.display = 'block';
		}

		document.getElementById('dismiss-cookie-notice')?.addEventListener('click', function() {
			localStorage.setItem(noticeKey, 'yes');
			if (banner) {
				banner.style.display = 'none';
			}
		});

		document.getElementById('close-cookie-notice')?.addEventListener('click', function() {
			localStorage.setItem(noticeKey, 'yes');
			if (banner) {
				banner.style.display = 'none';
			}
		});
	})();
</script>
<?php
}, 100);

?>
