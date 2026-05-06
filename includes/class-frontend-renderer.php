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
        $prompt_context = $this->get_prompt_context();

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

            $fallback_url = $this->build_provider_url($ai, $options['ai_prompt'], $post_url, $site_name, $page_title, $prompt_context);

            $output .= sprintf('<a href="%s" target="_blank" rel="noopener noreferrer" class="ai-share-btn" data-ai="%s" data-template="%s" data-site="%s" data-url="%s" data-title="%s" data-type="%s" data-post-type="%s" data-category="%s" data-tags="%s" data-excerpt="%s" data-schema-type="%s" data-author="%s" data-published-date="%s" data-faq-count="%s" data-base-url="%s" data-param-key="%s" data-supports-prefill="%s"%s>%s<span>%s</span></a>', esc_url($fallback_url), esc_attr($ai['id']), esc_attr($options['ai_prompt']), esc_attr($site_name), esc_attr($post_url), esc_attr($page_title), esc_attr($prompt_context['type']), esc_attr($prompt_context['post_type']), esc_attr($prompt_context['category']), esc_attr($prompt_context['tags']), esc_attr($prompt_context['excerpt']), esc_attr($prompt_context['schema_type']), esc_attr($prompt_context['author']), esc_attr($prompt_context['published_date']), esc_attr((string) $prompt_context['faq_count']), esc_url($ai['base_url']), esc_attr($ai['param_key']), $ai['supports_prefill'] ? '1' : '0', $onclick, $icon, esc_html($button_text));
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

    private function build_provider_url($provider, $template, $post_url, $site_name, $page_title, $prompt_context = array()) {
        if (empty($provider['base_url']) || empty($provider['param_key'])) {
            return $post_url;
        }

        $prompt_context = wp_parse_args(
            $prompt_context,
            array(
                'type' => '',
                'post_type' => '',
                'category' => '',
                'tags' => '',
                'excerpt' => '',
                'schema_type' => '',
                'author' => '',
                'published_date' => '',
                'faq_count' => 0,
            )
        );

        $prompt = str_replace(
            array('{URL}', '{SITE}', '{TITLE}', '{TYPE}', '{POST_TYPE}', '{CATEGORY}', '{TAGS}', '{EXCERPT}', '{SCHEMA_TYPE}', '{AUTHOR}', '{PUBLISHED_DATE}', '{FAQ_COUNT}'),
            array($post_url, $site_name, $page_title, $prompt_context['type'], $prompt_context['post_type'], $prompt_context['category'], $prompt_context['tags'], $prompt_context['excerpt'], $prompt_context['schema_type'], $prompt_context['author'], $prompt_context['published_date'], (string) $prompt_context['faq_count']),
            $template
        );

        return add_query_arg(
            $provider['param_key'],
            $prompt,
            $provider['base_url']
        );
    }

    private function get_prompt_context() {
        $post = get_post();
        if (!$post instanceof WP_Post) {
            return array(
                'type' => '',
                'post_type' => '',
                'category' => '',
                'tags' => '',
                'excerpt' => '',
                'schema_type' => '',
                'author' => '',
                'published_date' => '',
                'faq_count' => 0,
            );
        }

        $post_type = get_post_type($post);
        $post_type_object = get_post_type_object($post_type);
        $type_label = ($post_type_object && !empty($post_type_object->labels->singular_name)) ? $post_type_object->labels->singular_name : $post_type;

        $category_names = array();
        if (is_object_in_taxonomy($post_type, 'category')) {
            $categories = get_the_terms($post, 'category');
            if (!is_wp_error($categories) && !empty($categories)) {
                $category_names = wp_list_pluck($categories, 'name');
            }
        }

        $tag_names = array();
        if (is_object_in_taxonomy($post_type, 'post_tag')) {
            $tags = get_the_terms($post, 'post_tag');
            if (!is_wp_error($tags) && !empty($tags)) {
                $tag_names = wp_list_pluck($tags, 'name');
            }
        }

        $raw_excerpt = has_excerpt($post) ? $post->post_excerpt : wp_trim_words(wp_strip_all_tags($post->post_content), 40, '…');
        $schema_context = $this->resolve_schema_context($post);

        return array(
            'type' => $this->sanitize_prompt_value($type_label, 80),
            'post_type' => $this->sanitize_prompt_value($post_type, 40),
            'category' => $this->sanitize_prompt_value(implode(', ', array_map('strval', $category_names)), 200),
            'tags' => $this->sanitize_prompt_value(implode(', ', array_map('strval', $tag_names)), 200),
            'excerpt' => $this->sanitize_prompt_value($raw_excerpt, 400),
            'schema_type' => $schema_context['schema_type'],
            'author' => $schema_context['author'],
            'published_date' => $schema_context['published_date'],
            'faq_count' => (int) $schema_context['faq_count'],
        );
    }

    private function resolve_schema_context($post) {
        $context = array(
            'schema_type' => '',
            'author' => '',
            'published_date' => '',
            'faq_count' => 0,
        );

        if (!$post instanceof WP_Post) {
            return $context;
        }

        $schema_candidates = array(
            get_post_meta($post->ID, '_yoast_wpseo_schema_page_type', true),
            get_post_meta($post->ID, 'rank_math_rich_snippet', true),
            get_post_meta($post->ID, '_aioseo_schema_type', true),
            get_post_meta($post->ID, '_seopress_analysis_target_kw', true),
        );

        foreach ($schema_candidates as $candidate) {
            $candidate = $this->sanitize_prompt_value($candidate, 60);
            if ('' !== $candidate) {
                $context['schema_type'] = $candidate;
                break;
            }
        }

        if ('' === $context['schema_type']) {
            $post_type = get_post_type($post);
            $context['schema_type'] = $this->sanitize_prompt_value($post_type ? $post_type : 'post', 60);
        }

        $author_name = get_the_author_meta('display_name', (int) $post->post_author);
        if (empty($author_name)) {
            $author_name = get_the_author_meta('nickname', (int) $post->post_author);
        }
        $context['author'] = $this->sanitize_prompt_value($author_name, 80);

        $publish_timestamp = get_post_time('U', true, $post);
        if (!empty($publish_timestamp)) {
            $context['published_date'] = $this->sanitize_prompt_value(gmdate('Y-m-d', (int) $publish_timestamp), 10);
        }

        $faq_meta_candidates = array(
            get_post_meta($post->ID, 'rank_math_faq_schema', true),
            get_post_meta($post->ID, '_aioseo_faq_page', true),
            get_post_meta($post->ID, '_seopress_pro_rich_snippets_faq', true),
        );

        foreach ($faq_meta_candidates as $faq_meta) {
            $faq_count = $this->extract_item_count($faq_meta);
            if ($faq_count > $context['faq_count']) {
                $context['faq_count'] = $faq_count;
            }
        }

        $content_blob = strtolower((string) $post->post_content);
        if (false !== strpos($content_blob, 'wp:yoast/faq-block') || false !== strpos($content_blob, 'wp:rank-math/faq-block') || false !== strpos($content_blob, 'wp:aioseo/faq')) {
            $context['faq_count'] = max($context['faq_count'], 1);
        }

        return $context;
    }

    private function extract_item_count($value) {
        if (is_array($value)) {
            return count($value);
        }

        if (is_string($value) && '' !== $value) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return count($decoded);
            }

            if (false !== strpos(strtolower($value), 'faq')) {
                return 1;
            }
        }

        return 0;
    }

    private function sanitize_prompt_value($value, $max_length = 200) {
        $value = is_scalar($value) ? (string) $value : '';
        $value = sanitize_text_field(wp_strip_all_tags($value));
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, (int) $max_length);
        }

        return substr($value, 0, (int) $max_length);
    }
}

