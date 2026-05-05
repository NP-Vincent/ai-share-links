<?php
/**
 * Frontend rendering class.
 *
 * @package AIShareLinks
 */

defined('ABSPATH') || exit;

class AI_Share_Links_Frontend_Renderer {

    private $get_options;
    private $is_builder_preview;

    public function __construct(callable $get_options_callback, callable $is_builder_preview_callback) {
        $this->get_options = $get_options_callback;
        $this->is_builder_preview = $is_builder_preview_callback;
    }

    public function add_share_buttons($content) {
        if (!is_single() || !is_main_query() || call_user_func($this->is_builder_preview) || is_feed() || post_password_required()) {
            return $content;
        }

        $options = call_user_func($this->get_options);
        $buttons = $this->generate_share_buttons($options);
        if (empty($buttons)) {
            return $content;
        }

        switch ($options['position']) {
            case 'top':
                return $buttons . $content;
            case 'bottom':
                return $content . $buttons;
            case 'both':
                return $buttons . $content . $buttons;
            default:
                return $content;
        }
    }

    public function add_page_buttons() {
        if (!is_page() || is_admin() || call_user_func($this->is_builder_preview)) {
            return;
        }

        $options = call_user_func($this->get_options);
        $position = $options['position'] ?? 'top';
        $page_slugs = trim($options['page_slugs']);
        if (empty($page_slugs)) {
            return;
        }

        $current_slug = get_post_field('post_name', get_the_ID());
        $current_page_uri = get_page_uri(get_the_ID());
        $allowed_slugs = preg_split('/[\r\n,]+/', $page_slugs);
        $allowed_slugs = array_filter(array_map('trim', $allowed_slugs));
        if (!in_array($current_slug, $allowed_slugs, true) && !in_array($current_page_uri, $allowed_slugs, true)) {
            return;
        }

        $buttons = $this->generate_share_buttons($options);
        if (empty($buttons)) {
            return;
        }

        echo '<script type="text/javascript">document.addEventListener("DOMContentLoaded",function(){var content=document.querySelector(".entry-content, .page-content, main");if(content){var buttonMarkup=' . wp_json_encode($buttons) . ';var parser=new DOMParser();var parsedDocument=parser.parseFromString(buttonMarkup,"text/html");if(!parsedDocument||!parsedDocument.body||parsedDocument.body.children.length!==1){return;}var buttonContainer=parsedDocument.body.firstElementChild;if(!buttonContainer||!buttonContainer.classList.contains("ai-share-container")){return;}var position=' . wp_json_encode($position) . ';if(position==="top"){content.prepend(buttonContainer);}else if(position==="bottom"){content.appendChild(buttonContainer);}else if(position==="both"){var topButtonContainer=buttonContainer.cloneNode(true);content.prepend(topButtonContainer);content.appendChild(buttonContainer);}}});</script>';
    }

    private function generate_share_buttons($options) {
        if (empty($options['enabled_ais'])) {
            return '';
        }

        $post_url = esc_url(get_permalink());
        $page_title = get_the_title();
        $site_name = esc_attr(get_bloginfo('name'));
        $provider_map = $this->get_provider_map();

        $output = sprintf('<div class="%s" role="complementary" aria-label="%s">', esc_attr('ai-share-container'), esc_attr__('AI sharing options', AI_SHARE_LINKS_TEXT_DOMAIN));
        $output .= sprintf('<h4 class="ai-share-title">%s</h4>', esc_html($options['description']));
        $output .= '<div class="ai-share-buttons">';

        foreach ($options['enabled_ais'] as $ai_key) {
            if (!isset($provider_map[$ai_key])) {
                continue;
            }
            $ai = $provider_map[$ai_key];
            if (empty($ai['enabled'])) {
                continue;
            }
            $button_text = ('1' === $options['uppercase']) ? strtoupper($ai['label']) : $ai['label'];
            $icon = '';
            if ('emojis' === $options['icon_type']) {
                $icon = sprintf('<span class="ai-icon" aria-hidden="true">%s</span>', $ai['icon']);
            } elseif ('logos' === $options['icon_type']) {
                $icon = sprintf('<span class="ai-logo ai-logo-%s" aria-hidden="true"></span>', esc_attr($ai_key));
            }

            $onclick = ('1' === $options['ga_tracking'])
                ? sprintf(' onclick="if(typeof gtag!==\'undefined\'){gtag(\'event\',\'ai_share_click\',{\'ai_platform\':\'%s\',\'page_url\':window.location.href});}"', esc_js($ai_key))
                : '';

            $fallback_url = $this->build_provider_url($ai, $options['ai_prompt'], $post_url, $site_name, $page_title);

            $output .= sprintf('<a href="%s" target="_blank" rel="noopener noreferrer" class="ai-share-btn" data-ai="%s" data-template="%s" data-site="%s" data-url="%s" data-title="%s" data-base-url="%s" data-param-key="%s" data-supports-prefill="%s"%s>%s<span>%s</span></a>', esc_url($fallback_url), esc_attr($ai['id']), esc_attr($options['ai_prompt']), esc_attr($site_name), esc_attr($post_url), esc_attr($page_title), esc_url($ai['base_url']), esc_attr($ai['param_key']), $ai['supports_prefill'] ? '1' : '0', $onclick, $icon, esc_html($button_text));
        }

        $output .= '</div></div>';

        return $output;
    }

    public function get_provider_map() {
        return array(
            'perplexity' => array('id' => 'perplexity', 'label' => 'Perplexity', 'icon' => 'P', 'base_url' => 'https://www.perplexity.ai/search', 'param_key' => 'q', 'supports_prefill' => true, 'enabled' => true),
            'chatgpt' => array('id' => 'chatgpt', 'label' => 'ChatGPT', 'icon' => 'C', 'base_url' => 'https://chat.openai.com/', 'param_key' => 'q', 'supports_prefill' => true, 'enabled' => true),
            'claude' => array('id' => 'claude', 'label' => 'Claude', 'icon' => 'C', 'base_url' => 'https://claude.ai/new', 'param_key' => 'prompt', 'supports_prefill' => true, 'enabled' => true),
            'gemini' => array('id' => 'gemini', 'label' => 'Gemini', 'icon' => 'G', 'base_url' => 'https://gemini.google.com/app', 'param_key' => 'prompt', 'supports_prefill' => true, 'enabled' => true),
            'deepseek' => array('id' => 'deepseek', 'label' => 'DeepSeek', 'icon' => 'D', 'base_url' => 'https://chat.deepseek.com/', 'param_key' => 'q', 'supports_prefill' => true, 'enabled' => true),
        );
    }

    private function build_provider_url($provider, $template, $post_url, $site_name, $page_title) {
        if (empty($provider['base_url']) || empty($provider['param_key'])) {
            return $post_url;
        }

        $prompt = str_replace(
            array('{URL}', '{SITE}', '{TITLE}'),
            array($post_url, $site_name, $page_title),
            $template
        );

        return add_query_arg(
            $provider['param_key'],
            $prompt,
            $provider['base_url']
        );
    }
}
