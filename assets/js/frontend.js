(function () {
    'use strict';

    var CONFIG = window.aiShareLinksConfig || {};
    var providerConfig = {
        perplexity: { base: 'https://www.perplexity.ai/search', param: 'q' },
        chatgpt: { base: 'https://chat.openai.com/', param: 'q' },
        claude: { base: 'https://claude.ai/new', param: 'prompt' },
        gemini: { base: 'https://gemini.google.com/app', param: 'prompt' },
        deepseek: { base: 'https://chat.deepseek.com/', param: 'q' }
    };

    function getCanonicalUrl() {
        var canonical = document.querySelector('link[rel="canonical"]');
        return canonical && canonical.href ? canonical.href : window.location.href;
    }

    function applyPromptTemplate(template, context) {
        return (template || '')
            .replace(/\{URL\}/g, context.url)
            .replace(/\{SITE\}/g, context.site)
            .replace(/\{TITLE\}/g, context.title);
    }

    function buildProviderUrl(platform, prompt) {
        if (!providerConfig[platform] || !prompt) {
            return null;
        }

        var config = providerConfig[platform];
        return config.base + '?' + config.param + '=' + encodeURIComponent(prompt);
    }

    function setInlineStatus(button, message, isError) {
        if (!button) {
            return;
        }

        var statusEl = button.querySelector('.ai-share-inline-status');
        if (!statusEl) {
            statusEl = document.createElement('span');
            statusEl.className = 'ai-share-inline-status';
            statusEl.setAttribute('aria-live', 'polite');
            statusEl.style.display = 'block';
            statusEl.style.fontSize = '12px';
            statusEl.style.marginTop = '4px';
            button.appendChild(statusEl);
        }

        statusEl.textContent = message;
        statusEl.style.color = isError ? '#b91c1c' : 'inherit';

        window.setTimeout(function () {
            if (statusEl && statusEl.parentNode === button) {
                statusEl.textContent = '';
            }
        }, 3000);
    }

    function emitAnalytics(eventName, payload) {
        if (!CONFIG.gaTracking || typeof gtag === 'undefined') {
            return;
        }

        gtag('event', eventName, payload);
    }

    function copyWithExecCommand(prompt) {
        var textarea = document.createElement('textarea');
        textarea.value = prompt;
        textarea.setAttribute('readonly', 'readonly');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        textarea.style.pointerEvents = 'none';

        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        var copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (error) {
            copied = false;
        }

        document.body.removeChild(textarea);
        return copied;
    }

    function handleClipboardFallback(prompt, platform, button) {
        var fallbackMessage = 'Prompt copied — Paste into your AI assistant';
        var fallbackError = 'Could not copy prompt — Please copy manually';

        if (!prompt) {
            setInlineStatus(button, fallbackError, true);
            return;
        }

        function markFallbackSuccess() {
            emitAnalytics('ai_share_fallback_copy', {
                ai_platform: platform,
                page_url: window.location.href
            });
            setInlineStatus(button, fallbackMessage, false);
        }

        function markFallbackFailure() {
            setInlineStatus(button, fallbackError, true);
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(prompt)
                .then(markFallbackSuccess)
                .catch(function () {
                    if (copyWithExecCommand(prompt)) {
                        markFallbackSuccess();
                        return;
                    }
                    markFallbackFailure();
                });
            return;
        }

        if (copyWithExecCommand(prompt)) {
            markFallbackSuccess();
            return;
        }

        markFallbackFailure();
    }

    function getPromptFingerprint(platform, prompt) {
        var fingerprintSource = (platform || '') + '|' + (prompt || '');

        try {
            return btoa(unescape(encodeURIComponent(fingerprintSource)))
                .replace(/[^a-zA-Z0-9]/g, '')
                .slice(0, 24) || 'default';
        } catch (error) {
            return encodeURIComponent(fingerprintSource)
                .replace(/[^a-zA-Z0-9]/g, '')
                .slice(0, 24) || 'default';
        }
    }

    function getRemainingThrottleSeconds(lastClickTime) {
        return Math.ceil((30000 - (Date.now() - parseInt(lastClickTime, 10))) / 1000);
    }

    function handleThrottle(button, promptFingerprint) {
        var currentTime = Date.now();
        var lastClickKey = 'ai_share_last_click_' + promptFingerprint;
        var lastClickTime = localStorage.getItem(lastClickKey);

        if (!lastClickTime || (currentTime - parseInt(lastClickTime, 10)) >= 30000) {
            localStorage.setItem(lastClickKey, currentTime.toString());
            return false;
        }

        var remainingTime = getRemainingThrottleSeconds(lastClickTime);
        var originalText = button.querySelector('span:last-child').textContent;

        button.style.opacity = '0.5';
        button.style.pointerEvents = 'none';
        button.style.cursor = 'not-allowed';
        button.querySelector('span:last-child').textContent = 'Wait ' + remainingTime + 's';

        var countdown = setInterval(function () {
            var newRemainingTime = getRemainingThrottleSeconds(lastClickTime);
            if (newRemainingTime <= 0) {
                clearInterval(countdown);
                button.style.opacity = '1';
                button.style.pointerEvents = 'auto';
                button.style.cursor = 'pointer';
                button.querySelector('span:last-child').textContent = originalText;
                return;
            }

            button.querySelector('span:last-child').textContent = 'Wait ' + newRemainingTime + 's';
        }, 1000);

        return true;
    }

    function getPromptContext(button) {
        return {
            template: button.dataset.template || '',
            platform: button.dataset.ai,
            title: button.dataset.title || document.title || '',
            site: button.dataset.site || window.location.hostname || '',
            url: button.dataset.url || getCanonicalUrl()
        };
    }

    function openProviderOrFallback(platform, prompt, button) {
        var runtimeUrl = buildProviderUrl(platform, prompt);

        if (!runtimeUrl) {
            handleClipboardFallback(prompt, platform, button);
            return;
        }

        try {
            var openedWindow = window.open(runtimeUrl, '_blank', 'noopener,noreferrer');
            if (!openedWindow) {
                handleClipboardFallback(prompt, platform, button);
            }
        } catch (error) {
            handleClipboardFallback(prompt, platform, button);
        }
    }

    function onShareButtonClick(event) {
        event.preventDefault();

        var button = event.currentTarget;
        var context = getPromptContext(button);
        var prompt = applyPromptTemplate(context.template, {
            url: context.url,
            site: context.site,
            title: context.title
        });

        var promptFingerprint = getPromptFingerprint(context.platform, prompt);
        if (handleThrottle(button, promptFingerprint)) {
            return false;
        }

        openProviderOrFallback(context.platform, prompt, button);

        emitAnalytics('ai_share_click', {
            ai_platform: context.platform,
            page_url: window.location.href
        });

        return false;
    }

    function bindShareButton(button) {
        button.addEventListener('click', onShareButtonClick);
    }

    function initShareButtons() {
        document.querySelectorAll('.ai-share-btn').forEach(bindShareButton);
    }

    document.addEventListener('DOMContentLoaded', initShareButtons);
})();
