<?php
/**
 * Assets registration class.
 *
 * @package AIShareLinks
 */

defined('ABSPATH') || exit;

class AI_Share_Links_Assets {

    private $get_options;

    public function __construct(callable $get_options_callback) {
        $this->get_options = $get_options_callback;
    }

    public function enqueue_frontend_assets() {
        if (!is_singular() || is_admin()) {
            return;
        }

        wp_enqueue_style('ai-share-links-frontend', AI_SHARE_LINKS_PLUGIN_URL . 'assets/css/frontend.css', array(), AI_SHARE_LINKS_VERSION);
        wp_enqueue_script('ai-share-links-frontend', AI_SHARE_LINKS_PLUGIN_URL . 'assets/js/frontend.js', array(), AI_SHARE_LINKS_VERSION, true);

        $options = call_user_func($this->get_options);
        $renderer = new AI_Share_Links_Frontend_Renderer($this->get_options, static function () {
            return false;
        });

        wp_add_inline_script('ai-share-links-frontend', 'window.aiShareLinksConfig = ' . wp_json_encode(array(
            'gaTracking' => ('1' === $options['ga_tracking']),
            'providers' => $renderer->get_provider_map(),
        )) . ';', 'before');

        if ('1' === $options['compatibility_mode']) {
            add_action('wp_footer', array($this, 'render_compatibility_script'), 101);
        }
    }

    public function render_compatibility_script() {
        ?>
        <script id="ai-share-compat-js">
        document.addEventListener("DOMContentLoaded", function () {
            var containers = document.querySelectorAll(".entry-content, .page-content, main, article, .hentry, .post-content");
            containers.forEach(function (container) {
                var aiShares = container.querySelectorAll(":scope > .ai-share-container");
                if (aiShares.length === 0) return;
                var firstBar = aiShares[0];
                if (!firstBar || firstBar.parentNode !== container) return;
                var article = container.closest("article, .hentry, .post, .page") || container.parentElement;
                var title = article ? article.querySelector("h1.entry-title, .entry-header h1, h1.post-title, .post-title, .page-title") : null;
                if (title && title.parentNode) {
                    title.parentNode.insertBefore(firstBar, title.nextSibling);
                }
            });
        });
        </script>
        <?php
    }
}
