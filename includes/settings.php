<?php
if (!defined('ABSPATH')) exit;


// Settings page.
add_action('admin_menu', function() {
    add_options_page(
        __('CC SEO Enhancer', 'cc-seo-enhancer'),
        __('CC SEO Enhancer', 'cc-seo-enhancer'),
        'manage_options',
        'seo-enhancer-settings',
        'render_seo_enhancer_settings_page'
    );
});

add_action('admin_init', function() {
    cc_seo_enhancer_register_setting('seo_enhancer_ga_id');
    cc_seo_enhancer_register_setting('seo_enhancer_gtm_id');
    cc_seo_enhancer_register_setting('seo_enhancer_fb_pixel_id');
    cc_seo_enhancer_register_setting('seo_enhancer_google_verification');

    cc_seo_enhancer_register_setting('seo_enhancer_conversion_paths', 'sanitize_textarea_field'); // Per-path conversion tracking.
    // Switch checkbox enable/disable.
    cc_seo_enhancer_register_setting('enable_ga_tracking', 'cc_seo_enhancer_sanitize_checkbox');
    cc_seo_enhancer_register_setting('enable_gtm_tracking', 'cc_seo_enhancer_sanitize_checkbox');
    cc_seo_enhancer_register_setting('enable_fb_pixel', 'cc_seo_enhancer_sanitize_checkbox');
    cc_seo_enhancer_register_setting('enable_conversion_tracking', 'cc_seo_enhancer_sanitize_checkbox');
    cc_seo_enhancer_register_setting('enable_schema_output', 'cc_seo_enhancer_sanitize_checkbox');
    cc_seo_enhancer_register_setting('enable_cookie_consent', 'cc_seo_enhancer_sanitize_checkbox');
    cc_seo_enhancer_register_setting('seo_enhancer_bing_verification');

    // Website schema.
    cc_seo_enhancer_register_setting('seo_enhancer_site_alternate_name');

    // Organization schema.
    cc_seo_enhancer_register_setting('seo_enhancer_org_name');
    cc_seo_enhancer_register_setting('seo_enhancer_org_alternate_name');
    cc_seo_enhancer_register_setting('seo_enhancer_org_description', 'sanitize_textarea_field');
    cc_seo_enhancer_register_setting('seo_enhancer_org_url', 'esc_url_raw');
    cc_seo_enhancer_register_setting('seo_enhancer_org_logo', 'esc_url_raw');
    cc_seo_enhancer_register_setting('seo_enhancer_org_socials', 'sanitize_textarea_field');
    cc_seo_enhancer_register_setting('seo_enhancer_org_email', 'sanitize_email');
    cc_seo_enhancer_register_setting('seo_enhancer_org_contact');
    cc_seo_enhancer_register_setting('seo_enhancer_show_address', 'cc_seo_enhancer_sanitize_checkbox');
    cc_seo_enhancer_register_setting('seo_enhancer_org_country');
    cc_seo_enhancer_register_setting('seo_enhancer_org_region');
    cc_seo_enhancer_register_setting('seo_enhancer_org_city');
    cc_seo_enhancer_register_setting('seo_enhancer_org_address');
    cc_seo_enhancer_register_setting('seo_enhancer_is_personal', 'cc_seo_enhancer_sanitize_checkbox');
    cc_seo_enhancer_register_setting('seo_enhancer_disallow_paths', 'sanitize_textarea_field');
});

function cc_seo_enhancer_register_setting($option, $sanitize_callback = 'sanitize_text_field') {
    register_setting(
        'seo_enhancer_settings',
        $option,
        [
            'sanitize_callback' => $sanitize_callback,
        ]
    );
}

function cc_seo_enhancer_sanitize_checkbox($value) {
    return empty($value) ? 0 : 1;
}

function render_seo_enhancer_settings_page() { ?>

<div class="wrap seo-enhancer">
  <h1><?php esc_html_e('CC SEO Enhancer Settings', 'cc-seo-enhancer'); ?></h1>
  <form method="post" action="options.php">
      <?php settings_fields('seo_enhancer_settings'); ?>

<table class="form-table">

<tr>
    <th scope="row"><?php esc_html_e('Enable cookie notice', 'cc-seo-enhancer'); ?></th>
   <td colspan="2">
        <label class="switch">
            <input type="checkbox" name="enable_cookie_consent" value="1" <?php checked(1, get_option('enable_cookie_consent')); ?>>
            <span class="slider round"></span>
        </label>
        <p class="description"><?php esc_html_e('When enabled, a cookie notice bar appears at the bottom of the site. Tracking scripts are controlled by their own switches.', 'cc-seo-enhancer'); ?></p>
    </td>
</tr>

<tr>
  <th scope="row"><?php esc_html_e('Google Tag Manager ID', 'cc-seo-enhancer'); ?></th>
  <td colspan="2">
    <input type="text" name="seo_enhancer_gtm_id" value="<?php echo esc_attr(get_option('seo_enhancer_gtm_id')); ?>" placeholder="<?php echo esc_attr__('GTM-XXXXXXX', 'cc-seo-enhancer'); ?>" />
     <label class="switch">
      <input type="checkbox" name="enable_gtm_tracking" value="1" <?php checked(1, get_option('enable_gtm_tracking')); ?>>
      <span class="slider round"></span>
    </label><p class="description"><code>GTM-XXXXXXX</code></p>
  </td>
 
</tr>

<tr>
  <th scope="row"><?php esc_html_e('Google Analytics GA4 ID', 'cc-seo-enhancer'); ?></th>
 <td colspan="2">
    <input type="text" name="seo_enhancer_ga_id" value="<?php echo esc_attr(get_option('seo_enhancer_ga_id')); ?>" placeholder="<?php echo esc_attr__('G-XXXXXXXXXX', 'cc-seo-enhancer'); ?>" />
       <label class="switch">
      <input type="checkbox" name="enable_ga_tracking" value="1" <?php checked(1, get_option('enable_ga_tracking')); ?>>
      <span class="slider"></span>
    </label><p class="description"><code>G-XXXXXXXXXX</code></p>
  </td>
</tr>

<tr>
  <th scope="row"><?php esc_html_e('Facebook Pixel ID', 'cc-seo-enhancer'); ?></th>
 <td colspan="2">
    <input type="text" name="seo_enhancer_fb_pixel_id" value="<?php echo esc_attr(get_option('seo_enhancer_fb_pixel_id')); ?>" placeholder="<?php echo esc_attr__('123456789012345', 'cc-seo-enhancer'); ?>" />   <label class="switch">
      <input type="checkbox" name="enable_fb_pixel" value="1" <?php checked(1, get_option('enable_fb_pixel')); ?>>
      <span class="slider round"></span>
    </label>
    <p class="description"><code>123456789012345</code></p>

  </td>
</tr>

<tr>
  <th scope="row"><?php esc_html_e('Google Site Verification', 'cc-seo-enhancer'); ?></th>
  <td colspan="2">
    <input type="text" name="seo_enhancer_google_verification" value="<?php echo esc_attr(get_option('seo_enhancer_google_verification')); ?>" size="50" placeholder="<?php echo esc_attr__('Google verification token', 'cc-seo-enhancer'); ?>" />
    <p class="description">ex:<code>IzhIWeuO3QnwzwBof7yUypto0RjEf3ps5txAkWP4Cy8</code></p>
  </td>
</tr>

<tr>
    <th scope="row"><?php esc_html_e('Bing verification code', 'cc-seo-enhancer'); ?></th>
    <td colspan="2">
        <input type="text" name="seo_enhancer_bing_verification" value="<?php echo esc_attr(get_option('seo_enhancer_bing_verification')); ?>" size="50" placeholder="<?php echo esc_attr__('Bing verification token', 'cc-seo-enhancer'); ?>" />
        <p class="description"><?php esc_html_e('The msvalidate.01 content value. Example:', 'cc-seo-enhancer'); ?> <code>XXXXXXXXXXXXXXXXXXXX</code></p>
    </td>
</tr>

<tr>
  <th scope="row"><?php esc_html_e('Site settings', 'cc-seo-enhancer'); ?></th>
  <td colspan="2">

<strong><?php esc_html_e('Site name replacement', 'cc-seo-enhancer'); ?></strong><br>
<input type="text" 
       name="seo_enhancer_site_alternate_name" 
       value="<?php echo esc_attr(get_option('seo_enhancer_site_alternate_name')); ?>" 
       size="30"
       placeholder="<?php echo esc_attr__('Alternative site name', 'cc-seo-enhancer'); ?>" />
  </td>
</tr>

<tr>
  <th scope="row"><?php esc_html_e('Site representative type', 'cc-seo-enhancer'); ?></th>
  <td colspan="2">

    <fieldset>
      <?php
      // Read the stored setting; default to Organization when unset.
      $is_personal = get_option('seo_enhancer_is_personal', '0');
      ?>
      <label>
        <input type="radio"
               name="seo_enhancer_is_personal"
               value="<?php echo esc_attr('0'); ?>"
               <?php checked($is_personal, '0'); ?>>
        <?php esc_html_e('Organization', 'cc-seo-enhancer'); ?>
      </label><br>

      <label>
        <input type="radio"
               name="seo_enhancer_is_personal"
               value="<?php echo esc_attr('1'); ?>"
               <?php checked($is_personal, '1'); ?>>
        <?php esc_html_e('Person', 'cc-seo-enhancer'); ?>
      </label>
    </fieldset>

  </td>
</tr>

<tr>
  <th scope="row"></th>
  <td>
    <p><strong><?php esc_html_e('Representative name:', 'cc-seo-enhancer'); ?></strong><br>
    <input type="text" name="seo_enhancer_org_name" value="<?php echo esc_attr(get_option('seo_enhancer_org_name')); ?>" size="50" placeholder="<?php echo esc_attr__('Organization or person name', 'cc-seo-enhancer'); ?>" /></p>
        
    <p><strong><?php esc_html_e('Representative alternate name:', 'cc-seo-enhancer'); ?></strong><br>
    <input type="text" 
          name="seo_enhancer_org_alternate_name" 
          value="<?php echo esc_attr(get_option('seo_enhancer_org_alternate_name')); ?>" 
          size="50"
          placeholder="<?php echo esc_attr__('Alternate name', 'cc-seo-enhancer'); ?>" />

    <p><strong><?php esc_html_e('Representative description:', 'cc-seo-enhancer'); ?></strong><br>
    <textarea name="seo_enhancer_org_description" rows="3" cols="50" placeholder="<?php echo esc_attr__('Short organization or person description', 'cc-seo-enhancer'); ?>"><?php echo esc_textarea(get_option('seo_enhancer_org_description')); ?></textarea>

    <p><strong><?php esc_html_e('URL:', 'cc-seo-enhancer'); ?></strong><br>
    <input type="url" name="seo_enhancer_org_url" value="<?php echo esc_attr(get_option('seo_enhancer_org_url', home_url('/'))); ?>" size="50" placeholder="<?php echo esc_attr(home_url('/')); ?>" /></p>


      <?php
      $logo_url = esc_attr(get_option('seo_enhancer_org_logo'));
      $default_logo = get_avatar_url(0, ['default' => 'mystery', 'size' => 100]);
      $display_logo = $logo_url ?: $default_logo;
      ?>
      <p>
        <strong><?php esc_html_e('Personal avatar / organization logo:', 'cc-seo-enhancer'); ?></strong><br>
        <img id="org_logo_preview"
            src="<?php echo esc_url($display_logo); ?>"
            style="margin-top:10px;max-width:100px;display:block;border:1px solid #ccc;padding:4px;background:#fff;" />
        <br>
        <button class="button select-media" id="select_org_logo"><?php esc_html_e('Select image', 'cc-seo-enhancer'); ?></button>
        <input type="hidden" name="seo_enhancer_org_logo" id="org_logo"
              value="<?php echo esc_url($logo_url); ?>" />
      </p>

    <p><strong><?php esc_html_e('Social links, one per line:', 'cc-seo-enhancer'); ?></strong><br>
    <textarea name="seo_enhancer_org_socials" rows="3" cols="50" placeholder="<?php echo esc_attr__('https://facebook.com/yourpage', 'cc-seo-enhancer'); ?>"><?php echo esc_textarea(get_option('seo_enhancer_org_socials')); ?></textarea>
    <p class="description"><?php esc_html_e('Example:', 'cc-seo-enhancer'); ?> <code>https://facebook.com/yourpage</code></p>

    <p><strong><?php esc_html_e('Email:', 'cc-seo-enhancer'); ?></strong><br>
    <input type="text" name="seo_enhancer_org_email" value="<?php echo esc_attr(get_option('seo_enhancer_org_email')); ?>" size="30" placeholder="<?php echo esc_attr__('contact@example.com', 'cc-seo-enhancer'); ?>" /></p>

    
   <p><strong><?php esc_html_e('Enable address information:', 'cc-seo-enhancer'); ?></strong><br>
<span class="description"><?php esc_html_e('When enabled, phone, country, region, city, and address fields are shown.', 'cc-seo-enhancer'); ?></span>
<label class="switch" style="margin-top:5px;display:inline-block;">
  <input type="checkbox"
         id="toggle_address_fields"
         name="seo_enhancer_show_address"
         value="1"
         <?php checked(1, get_option('seo_enhancer_show_address')); ?>>
  <span class="slider round"></span>
</label>
</p>

<div id="address_fields_group" style="<?php echo esc_attr(get_option('seo_enhancer_show_address') ? '' : 'display:none;'); ?>">
  <p><strong><?php esc_html_e('Phone:', 'cc-seo-enhancer'); ?></strong><br>
  <input type="text" name="seo_enhancer_org_contact"
         value="<?php echo esc_attr(get_option('seo_enhancer_org_contact')); ?>" size="30" placeholder="<?php echo esc_attr__('+886-2-12345678', 'cc-seo-enhancer'); ?>" /><br>
  <span class="description"><?php esc_html_e('Example:', 'cc-seo-enhancer'); ?> <code>+886-2-12345678</code></span></p>

  <p><strong><?php esc_html_e('Country:', 'cc-seo-enhancer'); ?></strong><br>
  <input type="text" name="seo_enhancer_org_country"
         value="<?php echo esc_attr(get_option('seo_enhancer_org_country', 'TW')); ?>" size="10" placeholder="<?php echo esc_attr__('TW', 'cc-seo-enhancer'); ?>" /></p>

  <p><strong><?php esc_html_e('Region:', 'cc-seo-enhancer'); ?></strong><br>
  <input type="text" name="seo_enhancer_org_region"
         value="<?php echo esc_attr(get_option('seo_enhancer_org_region', 'Taiwan')); ?>" size="30" placeholder="<?php echo esc_attr__('Taiwan', 'cc-seo-enhancer'); ?>" /></p>

  <p><strong><?php esc_html_e('City:', 'cc-seo-enhancer'); ?></strong><br>
  <input type="text" name="seo_enhancer_org_city"
         value="<?php echo esc_attr(get_option('seo_enhancer_org_city')); ?>" size="30" placeholder="<?php echo esc_attr__('Taipei', 'cc-seo-enhancer'); ?>" /></p>

  <p><strong><?php esc_html_e('Address:', 'cc-seo-enhancer'); ?></strong><br>
  <input type="text" name="seo_enhancer_org_address"
         value="<?php echo esc_attr(get_option('seo_enhancer_org_address')); ?>" size="50" placeholder="<?php echo esc_attr__('Street address', 'cc-seo-enhancer'); ?>" /></p>
</div>


      </td>
    </tr>


<tr>
  <th scope="row"><?php esc_html_e('Conversion tracking URL list', 'cc-seo-enhancer'); ?></th>
  <td colspan="2">
    <label class="switch">
      <input type="checkbox" name="enable_conversion_tracking" value="1" <?php checked(1, get_option('enable_conversion_tracking')); ?>>
      <span class="slider round"></span>
    </label>
    <p class="description"><?php esc_html_e('When enabled, matching URL paths send GA4 events. Facebook Pixel conversion events are not sent from this list.', 'cc-seo-enhancer'); ?></p>
    <textarea name="seo_enhancer_conversion_paths" rows="4" cols="50" placeholder="<?php echo esc_attr__("/checkout/success=purchase\n/contact/thanks=generate_lead", 'cc-seo-enhancer'); ?>"><?php echo esc_textarea(get_option('seo_enhancer_conversion_paths')); ?></textarea>
    <p class="description"><?php esc_html_e('Example:', 'cc-seo-enhancer'); ?> <code>/checkout/success=purchase</code>, <code>/download=download</code>
    </p>
  </td>
</tr>


<tr>
  <th scope="row"><?php esc_html_e('robots.txt disallowed paths', 'cc-seo-enhancer'); ?></th>
  <td>
    <textarea name="seo_enhancer_disallow_paths" rows="4" cols="50" placeholder="<?php echo esc_attr__("/private/\n/temp/", 'cc-seo-enhancer'); ?>"><?php echo esc_textarea(get_option('seo_enhancer_disallow_paths')); ?></textarea>
    <p class="description"><?php esc_html_e('Enter one path per line, for example', 'cc-seo-enhancer'); ?> <code>/private/</code> <?php esc_html_e('or', 'cc-seo-enhancer'); ?> <code>/temp/</code>.</p>
  </td>
</tr>
</table>
            <?php submit_button(); ?>
        </form>
    </div>

    <?php
}

// Shared helper for other modules.
function seo_get_logo_url() {
  $custom_logo = trim(get_option('seo_enhancer_logo_url'));

  if ($custom_logo) {
      return $custom_logo; // Prefer the plugin setting when present.
  }

  // Fall back to the WordPress site logo.
  $custom_logo_id = get_theme_mod('custom_logo');
  if ($custom_logo_id) {
      $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
      if ($logo_url) {
          return $logo_url;
      }
  }

  // Use the bundled default logo when no site logo is available.
  return plugin_dir_url(__DIR__) . 'assets/default-logo.png';
}
