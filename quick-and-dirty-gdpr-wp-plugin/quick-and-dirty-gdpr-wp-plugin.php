<?php
/*
Plugin Name: Quick and Dirty GDPR Wordpress Plugin
Description: Displays a customizable popup with image, text, and privacy policy link.
Version: 1.0
Author: Michał Brennek
Text Domain: custom-popup-plugin
License: CC BY-NC-SA 4.0
*/

add_action('plugins_loaded', 'custom_popup_load_textdomain');

function custom_popup_load_textdomain() {
    load_plugin_textdomain('custom-popup-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

add_action('admin_menu', 'custom_popup_menu');
add_action('admin_init', 'custom_popup_settings');
add_action('wp_footer', 'custom_popup_display');

function custom_popup_menu() {
    add_options_page(__('Custom Popup Settings', 'custom-popup-plugin'), __('Custom Popup', 'custom-popup-plugin'), 'manage_options', 'custom-popup-settings', 'custom_popup_settings_page');
}

function custom_popup_settings() {
    add_option('custom_popup_texts', []);
    add_option('custom_popup_privacy_texts', []);
    add_option('custom_popup_accept_texts', []);
    add_option('custom_popup_image', '');
    add_option('custom_popup_scale', '2');
    add_option('custom_popup_offset', '20');
    add_option('custom_popup_privacy_page', '');

    register_setting('custom_popup_options_group', 'custom_popup_texts');
    register_setting('custom_popup_options_group', 'custom_popup_privacy_texts');
    register_setting('custom_popup_options_group', 'custom_popup_accept_texts');
    register_setting('custom_popup_options_group', 'custom_popup_image');
    register_setting('custom_popup_options_group', 'custom_popup_scale');
    register_setting('custom_popup_options_group', 'custom_popup_offset');
    register_setting('custom_popup_options_group', 'custom_popup_privacy_page');
}

function custom_popup_settings_page() {
    $languages = ['en' => 'English', 'pl' => 'Polish', 'de' => 'German'];
    $pages = get_pages();
    ?>
    <div class="wrap">
        <h1><?php _e('Custom Popup Settings', 'custom-popup-plugin'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('custom_popup_options_group'); ?>
            <table class="form-table">
                <?php foreach ($languages as $code => $label): ?>
                <tr valign="top">
                    <th scope="row"><?php echo __('Popup Text', 'custom-popup-plugin') . " ($label)"; ?></th>
                    <td><textarea name="custom_popup_texts[<?php echo $code; ?>]" rows="4" cols="80"><?php echo esc_textarea(get_option('custom_popup_texts')[$code] ?? ''); ?></textarea></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo __('Privacy Policy Link Text', 'custom-popup-plugin') . " ($label)"; ?></th>
                    <td><input type="text" name="custom_popup_privacy_texts[<?php echo $code; ?>]" value="<?php echo esc_attr(get_option('custom_popup_privacy_texts')[$code] ?? ''); ?>" size="80" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo __('Accept Button Text', 'custom-popup-plugin') . " ($label)"; ?></th>
                    <td><input type="text" name="custom_popup_accept_texts[<?php echo $code; ?>]" value="<?php echo esc_attr(get_option('custom_popup_accept_texts')[$code] ?? ''); ?>" size="80" /></td>
                </tr>
                <?php endforeach; ?>
                <tr valign="top">
                    <th scope="row"><?php _e('Image URL', 'custom-popup-plugin'); ?></th>
                    <td><input type="text" name="custom_popup_image" value="<?php echo esc_attr(get_option('custom_popup_image')); ?>" size="80" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Image Scale', 'custom-popup-plugin'); ?></th>
                    <td><input type="number" name="custom_popup_scale" value="<?php echo esc_attr(get_option('custom_popup_scale')); ?>" step="0.1" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Image Vertical Offset', 'custom-popup-plugin'); ?></th>
                    <td><input type="number" name="custom_popup_offset" value="<?php echo esc_attr(get_option('custom_popup_offset')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Privacy Policy Page', 'custom-popup-plugin'); ?></th>
                    <td>
                        <select name="custom_popup_privacy_page">
                            <?php foreach ($pages as $page): ?>
                                <option value="<?php echo $page->ID; ?>" <?php selected(get_option('custom_popup_privacy_page'), $page->ID); ?>><?php echo esc_html($page->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Help', 'custom-popup-plugin'); ?></th>
                    <td><?php _e('Use [privacy-policy] to insert the privacy policy link and [accept] to insert the accept button.', 'custom-popup-plugin'); ?></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function custom_popup_display() {
    if (strpos($_SERVER['REQUEST_URI'], 'wp-admin') !== false) return;
    if (isset($_COOKIE['custom_popup_closed'])) return;

    $locale = substr(get_locale(), 0, 2);
    $texts = get_option('custom_popup_texts');
    $privacy_texts = get_option('custom_popup_privacy_texts');
    $accept_texts = get_option('custom_popup_accept_texts');
    $text = $texts[$locale] ?? $texts['en'] ?? '';
    $privacy_text = $privacy_texts[$locale] ?? $privacy_texts['en'] ?? 'Privacy Policy';
    $accept_text = $accept_texts[$locale] ?? $accept_texts['en'] ?? 'Accept';
    $privacy_page_id = get_option('custom_popup_privacy_page');
    $privacy_url = get_permalink($privacy_page_id);
	
	$image_url = get_option('custom_popup_image');
	if (empty($image_url)) {
   		 $image_url = plugins_url('assets/cookie.png', __FILE__);
	}
	$image_url = esc_url($image_url);

    $scale = floatval(get_option('custom_popup_scale'));
    $offset = intval(get_option('custom_popup_offset'));

    $text = str_replace('[privacy-policy]', '<a href="' . esc_url($privacy_url) . '" target="_blank">' . esc_html($privacy_text) . '</a>', $text);
    $text = str_replace('[accept]', '<button onclick="document.getElementById(\'custom-popup\').style.display=\'none\';document.cookie=\'custom_popup_closed=true;max-age=' . (90 * 24 * 60 * 60) . '\';">' . esc_html($accept_text) . '</button>', $text);

    echo '
    <div id="custom-popup" style="position:fixed;bottom:0;width:100%;background:#f9f9f9;padding:20px;border-top:1px solid #ccc;z-index:9999;">
        <div style="max-width:800px;margin:auto;text-align:center;position:relative;">
            <img src="' . $image_url . '" style="position:absolute;top:-' . $offset . 'px;left:50%;transform:translateX(-50%) scale(' . $scale . ');max-height:300px;z-index:10000;" />
            <div>' . $text . '</div>
        </div>
    </div>';
}
?>
