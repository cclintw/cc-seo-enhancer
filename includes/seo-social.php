<?php
if (!defined('ABSPATH')) {
    exit;
}

// Display social profile fields on user edit screens.
add_action('show_user_profile', 'cc_add_social_fields');
add_action('edit_user_profile', 'cc_add_social_fields');

function cc_add_social_fields($user) {
?>
<h3><?php esc_html_e('Social links', 'cc-seo-enhancer'); ?></h3>
<table class="form-table">
    <tr>
        <th><label for="facebook">Facebook</label></th>
        <td><input type="url" name="facebook" id="facebook"
            value="<?php echo esc_attr(get_the_author_meta('facebook', $user->ID)); ?>"
            placeholder="<?php echo esc_attr__('https://facebook.com/username', 'cc-seo-enhancer'); ?>"
            class="regular-text" /></td>
    </tr>
    <tr>
        <th><label for="twitter">Twitter</label></th>
        <td><input type="url" name="twitter" id="twitter"
            value="<?php echo esc_attr(get_the_author_meta('twitter', $user->ID)); ?>"
            placeholder="<?php echo esc_attr__('https://x.com/username', 'cc-seo-enhancer'); ?>"
            class="regular-text" /></td>
    </tr>
    <tr>
        <th><label for="linkedin">LinkedIn</label></th>
        <td><input type="url" name="linkedin" id="linkedin"
            value="<?php echo esc_attr(get_the_author_meta('linkedin', $user->ID)); ?>"
            placeholder="<?php echo esc_attr__('https://linkedin.com/in/username', 'cc-seo-enhancer'); ?>"
            class="regular-text" /></td>
    </tr>
</table>
<?php wp_nonce_field('cc_seo_social_fields', 'cc_seo_social_fields_nonce'); ?>
<?php
}

// Save social profile fields.
add_action('personal_options_update', 'cc_save_social_fields');
add_action('edit_user_profile_update', 'cc_save_social_fields');

function cc_save_social_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) return false;
    if (
        !isset($_POST['cc_seo_social_fields_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cc_seo_social_fields_nonce'])), 'cc_seo_social_fields')
    ) {
        return false;
    }

    $facebook = isset($_POST['facebook']) ? esc_url_raw(wp_unslash($_POST['facebook'])) : '';
    $twitter  = isset($_POST['twitter']) ? esc_url_raw(wp_unslash($_POST['twitter'])) : '';
    $linkedin = isset($_POST['linkedin']) ? esc_url_raw(wp_unslash($_POST['linkedin'])) : '';

    update_user_meta($user_id, 'facebook', $facebook);
    update_user_meta($user_id, 'twitter', $twitter);
    update_user_meta($user_id, 'linkedin', $linkedin);
}
