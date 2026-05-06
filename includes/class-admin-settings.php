<?php
/**
 * Admin settings class.
 *
 * @package AIShareLinks
 */

defined('ABSPATH') || exit;

class AI_Share_Links_Admin_Settings {

    private $get_options;

    public function __construct(callable $get_options_callback) {
        $this->get_options = $get_options_callback;
    }

    public function add_admin_menu() {
        add_options_page(
            __('AI Share Links', AI_SHARE_LINKS_TEXT_DOMAIN),
            __('AI Share Links', AI_SHARE_LINKS_TEXT_DOMAIN),
            'manage_options',
            'ai-share-links',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        $options = call_user_func($this->get_options);
        $renderer = new AI_Share_Links_Frontend_Renderer($this->get_options, static function () {
            return false;
        });
        $provider_map = $renderer->get_provider_map();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('AI Share Links Settings', AI_SHARE_LINKS_TEXT_DOMAIN); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('ai_share_links_options_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Position', AI_SHARE_LINKS_TEXT_DOMAIN); ?></th>
                        <td>
                            <select name="ai_share_links_options[position]">
                                <option value="top" <?php selected($options['position'], 'top'); ?>><?php esc_html_e('Top only', AI_SHARE_LINKS_TEXT_DOMAIN); ?></option>
                                <option value="bottom" <?php selected($options['position'], 'bottom'); ?>><?php esc_html_e('Bottom only', AI_SHARE_LINKS_TEXT_DOMAIN); ?></option>
                                <option value="both" <?php selected($options['position'], 'both'); ?>><?php esc_html_e('Both', AI_SHARE_LINKS_TEXT_DOMAIN); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Theme Styling', AI_SHARE_LINKS_TEXT_DOMAIN); ?></th>
                        <td><p class="description"><?php esc_html_e('AI Share Links now inherits your active theme styles (colors, typography, spacing, and button appearance) automatically.', AI_SHARE_LINKS_TEXT_DOMAIN); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Icon Type', AI_SHARE_LINKS_TEXT_DOMAIN); ?></th>
                        <td>
                            <select name="ai_share_links_options[icon_type]">
                                <option value="logos" <?php selected($options['icon_type'], 'logos'); ?>><?php esc_html_e('Logos', AI_SHARE_LINKS_TEXT_DOMAIN); ?></option>
                                <option value="emojis" <?php selected($options['icon_type'], 'emojis'); ?>><?php esc_html_e('Emojis', AI_SHARE_LINKS_TEXT_DOMAIN); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr><th scope="row"><?php esc_html_e('Uppercase Button Text', AI_SHARE_LINKS_TEXT_DOMAIN); ?></th><td><input type="checkbox" name="ai_share_links_options[uppercase]" value="1" <?php checked($options['uppercase'], '1'); ?> /></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Description Text', AI_SHARE_LINKS_TEXT_DOMAIN); ?></th><td><input type="text" name="ai_share_links_options[description]" value="<?php echo esc_attr($options['description']); ?>" class="regular-text" /></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Enable Google Analytics Tracking', AI_SHARE_LINKS_TEXT_DOMAIN); ?></th><td><input type="checkbox" name="ai_share_links_options[ga_tracking]" value="1" <?php checked($options['ga_tracking'], '1'); ?> /></td></tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Enabled AI Platforms', AI_SHARE_LINKS_TEXT_DOMAIN); ?></th>
                        <td>
                            <?php
                            foreach ($provider_map as $key => $provider):
                                $name = isset($provider['label']) ? $provider['label'] : $key;
                                $mode_label = isset($provider['recommended_mode']) ? $provider['recommended_mode'] : 'auto';
                                $mode_description = isset($provider['mode_description']) ? $provider['mode_description'] : '';
                                ?>
                                <label><input type="checkbox" name="ai_share_links_options[enabled_ais][]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $options['enabled_ais'], true)); ?> /> <?php echo esc_html($name); ?></label>
                                <p class="description" style="margin:4px 0 10px 22px;"><?php echo esc_html(sprintf(__('Mode: %1$s. %2$s', AI_SHARE_LINKS_TEXT_DOMAIN), $mode_label, $mode_description)); ?></p>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr><th scope="row"><?php esc_html_e('AI Prompt Template', AI_SHARE_LINKS_TEXT_DOMAIN); ?></th><td><textarea name="ai_share_links_options[ai_prompt]" rows="5" class="large-text code"><?php echo esc_textarea($options['ai_prompt']); ?></textarea><p class="description"><?php esc_html_e('Supported tokens: {URL}, {SITE}, {TITLE}, {TYPE}, {POST_TYPE}, {CATEGORY}, {TAGS}, {EXCERPT}, {SCHEMA_TYPE}, {AUTHOR}, {PUBLISHED_DATE}, {FAQ_COUNT}. Prompt values are replaced at click time; tokens with no available value are replaced with an empty string. Href links remain as fallback.', AI_SHARE_LINKS_TEXT_DOMAIN); ?></p></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Show on Pages (slugs/paths)', AI_SHARE_LINKS_TEXT_DOMAIN); ?></th><td><textarea name="ai_share_links_options[page_slugs]" rows="6" class="large-text code" placeholder="about,pricing&#10;resources/guides/getting-started"><?php echo esc_textarea($options['page_slugs']); ?></textarea><p class="description"><?php esc_html_e('Enter slugs or page paths separated by commas or new lines. Leave empty to disable on pages.', AI_SHARE_LINKS_TEXT_DOMAIN); ?></p></td></tr>
                    <tr><th scope="row"><label for="ai_share_links_options[compatibility_mode]"><?php esc_html_e('Compatibility Mode', AI_SHARE_LINKS_TEXT_DOMAIN); ?></label></th><td><input type="checkbox" id="ai_share_links_options[compatibility_mode]" name="ai_share_links_options[compatibility_mode]" value="1" <?php checked($options['compatibility_mode'], '1'); ?> /><p class="description"><?php esc_html_e('Enable this on themes that prepend the featured image AFTER the_content filters (causing overlap). This moves the AI bar to the very top using JavaScript.', AI_SHARE_LINKS_TEXT_DOMAIN); ?></p></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_admin_assets($hook) {
        if ('settings_page_ai-share-links' !== $hook) {
            return;
        }

        wp_add_inline_style('wp-admin', $this->get_admin_css());
        wp_add_inline_script('wp-admin', $this->get_admin_js());
    }

    private function get_admin_css() {
        return '.scheme-preview-grid{display:flex;flex-wrap:wrap;gap:10px;margin-top:10px}.scheme-preview-card-mini{border-radius:6px;overflow:hidden;width:auto;box-shadow:0 2px 6px rgba(0,0,0,0.1)}.scheme-preview-mini{padding:15px;color:white;text-shadow:0 1px 2px rgba(0,0,0,0.5);text-align:center}.scheme-name-mini{font-weight:bold;font-size:11px}.scheme-blue{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)}.scheme-salmon{background:linear-gradient(135deg,#ff9a8b 0%,#fecfef 100%)}.scheme-forest{background:linear-gradient(135deg,#134e5e 0%,#71b280 100%)}.scheme-seafoam{background:linear-gradient(135deg,#a8edea 0%,#fed6e3 100%);color:#333;text-shadow:none}.scheme-seafoam .scheme-name-mini{color:#333}.scheme-cosmic{background:linear-gradient(135deg,#8B4513 0%,#9b59b6 50%,#FFD700 100%)}.scheme-brand{background:linear-gradient(135deg,#011949 0%,#0a4fa6 100%)}.scheme-brand-transparent{background:transparent;border:2px solid #011949;color:#011949;text-shadow:none}.scheme-brand-transparent .scheme-name-mini{color:#011949}.scheme-solid-navy{background:#011949}.scheme-midnight-aurora{background:linear-gradient(135deg,#011949 0%,#00d4ff 100%)}';
    }

    private function get_admin_js() {
        return '';
    }
}
