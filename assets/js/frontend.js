(function () {
    'use strict';

    var CONFIG = window.aiShareLinksConfig || {};
    var PROVIDERS = CONFIG.providers || {};

    function getCanonicalUrl() {
        var canonical = document.querySelector('link[rel="canonical"]');
        return canonical && canonical.href ? canonical.href : window.location.href;
    }

    function applyPromptTemplate(template, context) {
        return (template || '')
            .replace(/\{URL\}/g, context.url)
            .replace(/\{SITE\}/g, context.site)
            .replace(/\{TITLE\}/g, context.title)
            .replace(/\{TYPE\}/g, context.type)
            .replace(/\{POST_TYPE\}/g, context.postType)
            .replace(/\{CATEGORY\}/g, context.category)
            .replace(/\{TAGS\}/g, context.tags)
            .replace(/\{EXCERPT\}/g, context.excerpt);
    }

    function buildProviderUrl(platform, prompt) {
        var config = PROVIDERS[platform];
        if (!config || !config.base_url || !config.param_key || !prompt) {
            return null;
        }

        return config.base_url + '?' + encodeURIComponent(config.param_key) + '=' + encodeURIComponent(prompt);
    }

    var STATUS_MESSAGES = { copied_and_opened: { message: 'Opened with prefilled prompt.', isError: false }, copied_only: { message: 'Copied prompt — paste it in chat to continue.', isError: false }, popup_blocked_copied: { message: 'Popup blocked — prompt copied. Allow popups to open provider automatically.', isError: false }, prefill_unstable_copied: { message: 'Prefill is not reliable for this provider right now — prompt copied for manual paste.', isError: false }, copy_failed: { message: 'Unable to copy automatically — copy the prompt manually and paste into chat.', isError: true } };
    function setInlineStatus(button, message, isError) { if (!button) return; var statusEl = button.querySelector('.ai-share-inline-status'); if (!statusEl) { statusEl = document.createElement('span'); statusEl.className = 'ai-share-inline-status'; statusEl.setAttribute('aria-live', 'polite'); statusEl.style.display = 'block'; statusEl.style.fontSize = '12px'; statusEl.style.marginTop = '4px'; button.appendChild(statusEl);} statusEl.textContent = message; statusEl.style.color = isError ? '#b91c1c' : 'inherit'; window.setTimeout(function () { if (statusEl && statusEl.parentNode === button) { statusEl.textContent = ''; } }, 3000); }
    function showInlineStatus(button, outcome) { var details = STATUS_MESSAGES[outcome] || STATUS_MESSAGES.copy_failed; setInlineStatus(button, details.message, details.isError); }
    function emitAnalytics(eventName, payload) { if (!CONFIG.gaTracking || typeof gtag === 'undefined') return; gtag('event', eventName, payload); }
    function copyWithExecCommand(prompt) { var textarea = document.createElement('textarea'); textarea.value = prompt; textarea.setAttribute('readonly', 'readonly'); textarea.style.position = 'fixed'; textarea.style.opacity = '0'; textarea.style.pointerEvents = 'none'; document.body.appendChild(textarea); textarea.focus(); textarea.select(); var copied = false; try { copied = document.execCommand('copy'); } catch (error) { copied = false; } document.body.removeChild(textarea); return copied; }
    function handleClipboardFallback(prompt, platform, button, outcomeOnSuccess) { var successOutcome = outcomeOnSuccess || 'copied_only'; if (!prompt) { showInlineStatus(button, 'copy_failed'); return; } function markFallbackSuccess() { emitAnalytics('ai_share_fallback_copy', { ai_platform: platform, page_url: window.location.href, outcome_subtype: successOutcome }); showInlineStatus(button, successOutcome);} function markFallbackFailure() { emitAnalytics('ai_share_fallback_copy', { ai_platform: platform, page_url: window.location.href, outcome_subtype: 'copy_failed' }); showInlineStatus(button, 'copy_failed');} if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(prompt).then(markFallbackSuccess).catch(function () { if (copyWithExecCommand(prompt)) { markFallbackSuccess(); return; } markFallbackFailure(); }); return; } if (copyWithExecCommand(prompt)) { markFallbackSuccess(); return; } markFallbackFailure(); }
    function getPromptFingerprint(platform, prompt) { var fingerprintSource = (platform || '') + '|' + (prompt || ''); try { return btoa(unescape(encodeURIComponent(fingerprintSource))).replace(/[^a-zA-Z0-9]/g, '').slice(0, 24) || 'default'; } catch (error) { return encodeURIComponent(fingerprintSource).replace(/[^a-zA-Z0-9]/g, '').slice(0, 24) || 'default'; } }
    function getRemainingThrottleSeconds(lastClickTime) { return Math.ceil((30000 - (Date.now() - parseInt(lastClickTime, 10))) / 1000); }
    function handleThrottle(button, promptFingerprint) { var currentTime = Date.now(); var lastClickKey = 'ai_share_last_click_' + promptFingerprint; var lastClickTime = localStorage.getItem(lastClickKey); if (!lastClickTime || (currentTime - parseInt(lastClickTime, 10)) >= 30000) { localStorage.setItem(lastClickKey, currentTime.toString()); return false; } var remainingTime = getRemainingThrottleSeconds(lastClickTime); var originalText = button.querySelector('span:last-child').textContent; button.style.opacity = '0.5'; button.style.pointerEvents = 'none'; button.style.cursor = 'not-allowed'; button.querySelector('span:last-child').textContent = 'Wait ' + remainingTime + 's'; var countdown = setInterval(function () { var newRemainingTime = getRemainingThrottleSeconds(lastClickTime); if (newRemainingTime <= 0) { clearInterval(countdown); button.style.opacity = '1'; button.style.pointerEvents = 'auto'; button.style.cursor = 'pointer'; button.querySelector('span:last-child').textContent = originalText; return; } button.querySelector('span:last-child').textContent = 'Wait ' + newRemainingTime + 's'; }, 1000); return true; }
    function getPromptContext(button) { return { template: button.dataset.template || '', platform: button.dataset.ai, title: button.dataset.title || document.title || '', site: button.dataset.site || window.location.hostname || '', url: button.dataset.url || getCanonicalUrl(), type: button.dataset.type || '', postType: button.dataset.postType || '', category: button.dataset.category || '', tags: button.dataset.tags || '', excerpt: button.dataset.excerpt || '' }; }

    function resolveProviderMode(provider) {
        var recommendedMode = provider.recommended_mode || 'auto';
        if (recommendedMode === 'prefill' || recommendedMode === 'copy_only') {
            return recommendedMode;
        }

        if (!provider.supports_prefill) {
            return 'copy_only';
        }

        if (provider.prefill_stability === 'stable') {
            return 'prefill';
        }

        return 'copy_only';
    }

    function openProviderOrFallback(platform, prompt, button) {
        var provider = PROVIDERS[platform] || {};
        var selectedMode = resolveProviderMode(provider);

        if (selectedMode === 'copy_only') {
            var copyOutcome = provider.supports_prefill ? 'prefill_unstable_copied' : 'copied_only';
            handleClipboardFallback(prompt, platform, button, copyOutcome);
            return copyOutcome;
        }

        var runtimeUrl = buildProviderUrl(platform, prompt);
        if (!runtimeUrl) {
            handleClipboardFallback(prompt, platform, button, 'copied_only');
            return 'copied_only';
        }

        try {
            var openedWindow = window.open(runtimeUrl, '_blank', 'noopener,noreferrer');
            if (!openedWindow) {
                handleClipboardFallback(prompt, platform, button, 'popup_blocked_copied');
                return 'popup_blocked_copied';
            }
            showInlineStatus(button, 'copied_and_opened');
            return 'copied_and_opened';
        } catch (error) {
            handleClipboardFallback(prompt, platform, button, 'popup_blocked_copied');
            return 'popup_blocked_copied';
        }
    }

    function onShareButtonClick(event) { event.preventDefault(); var button = event.currentTarget; var context = getPromptContext(button); var prompt = applyPromptTemplate(context.template, { url: context.url, site: context.site, title: context.title, type: context.type, postType: context.postType, category: context.category, tags: context.tags, excerpt: context.excerpt }); var promptFingerprint = getPromptFingerprint(context.platform, prompt); if (handleThrottle(button, promptFingerprint)) return false; var outcomeSubtype = openProviderOrFallback(context.platform, prompt, button); emitAnalytics('ai_share_click', { ai_platform: context.platform, page_url: window.location.href, outcome_subtype: outcomeSubtype || 'copy_pending' }); return false; }
    function bindShareButton(button) { button.addEventListener('click', onShareButtonClick); }
    function initShareButtons() { document.querySelectorAll('.ai-share-btn').forEach(bindShareButton); }
    document.addEventListener('DOMContentLoaded', initShareButtons);
})();
