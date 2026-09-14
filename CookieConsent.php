<?php
/**
 * Plugin Name: Lightweight Cookie Consent & Script Blocker
 * Description: Banner di consenso cookie leggero con CookieConsent v3 e blocco preventivo degli script.
 * Version: 1.0.0
 * Author: pepcoiro
 */

if (!defined('ABSPATH')) {
    exit; // Evita l'accesso diretto
}

/* ==========================================================================
   0. CONFIGURAZIONE & COSTANTI
   ========================================================================== */

if (!defined('CC_VERSION'))      { define('CC_VERSION', '3.1.0'); }
if (!defined('CC_CONSENT_MODE')) { define('CC_CONSENT_MODE', true); }
if (!defined('CC_CM_REGIONS'))   { define('CC_CM_REGIONS', []); }
if (!defined('CC_SRI_CSS'))      { define('CC_SRI_CSS', ''); }
if (!defined('CC_SRI_JS'))       { define('CC_SRI_JS', '');  }
if (!defined('CC_DEBUG'))        { define('CC_DEBUG', true); }

/**
 * Mappa dei servizi esterni da identificare negli script in coda/inline.
 */
function cc_service_map() {
    return [
        // ---- Google ----
        ['match' => 'gtm.js',               'cat' => 'analytics', 'service' => 'Google Tag Manager'],
        ['match' => 'gtag/js',              'cat' => 'analytics', 'service' => 'Google Analytics'],
        ['match' => 'googletagmanager',     'cat' => 'analytics', 'service' => 'Google Tag Manager'],
        ['match' => 'google-analytics.com', 'cat' => 'analytics', 'service' => 'Google Analytics'],
        ['match' => 'google-analytics',     'cat' => 'analytics', 'service' => 'Google Analytics'],
        ['match' => 'googlesyndication',    'cat' => 'marketing', 'service' => 'Google Ads'],
        ['match' => 'googleadservices',     'cat' => 'marketing', 'service' => 'Google Ads'],
        ['match' => 'doubleclick',          'cat' => 'marketing', 'service' => 'Google Ads'],
        ['match' => 'google-ads',           'cat' => 'marketing', 'service' => 'Google Ads'],
        // ---- Analytics ----
        ['match' => 'clarity.ms',           'cat' => 'analytics', 'service' => 'Microsoft Clarity'],
        ['match' => 'clarity',              'cat' => 'analytics', 'service' => 'Microsoft Clarity'],
        ['match' => 'matomo',               'cat' => 'analytics', 'service' => 'Matomo'],
        ['match' => 'plausible',            'cat' => 'analytics', 'service' => 'Plausible'],
        ['match' => 'umami',                'cat' => 'analytics', 'service' => 'Umami'],
        ['match' => 'hotjar',               'cat' => 'analytics', 'service' => 'Hotjar'],
        ['match' => 'fathom',               'cat' => 'analytics', 'service' => 'Fathom'],
        ['match' => 'rudderstack',          'cat' => 'analytics', 'service' => 'RudderStack'],
        // ---- Marketing ----
        ['match' => 'connect.facebook.net', 'cat' => 'marketing', 'service' => 'Meta Pixel'],
        ['match' => 'fbevents',             'cat' => 'marketing', 'service' => 'Meta Pixel'],
        ['match' => 'fbq',                  'cat' => 'marketing', 'service' => 'Meta Pixel'],
        ['match' => 'bat.bing',             'cat' => 'marketing', 'service' => 'Microsoft Ads'],
        ['match' => 'snap.licdn',           'cat' => 'marketing', 'service' => 'LinkedIn Insight Tag'],
        ['match' => 'licdn.com',            'cat' => 'marketing', 'service' => 'LinkedIn Insight Tag'],
        ['match' => 'analytics.tiktok',     'cat' => 'marketing', 'service' => 'TikTok Pixel'],
        ['match' => 'ct.pinterest',         'cat' => 'marketing', 'service' => 'Pinterest Tag'],
        ['match' => 'sc-static.net',        'cat' => 'marketing', 'service' => 'Snapchat Pixel'],
        ['match' => 'hubspot',              'cat' => 'marketing', 'service' => 'HubSpot'],
        ['match' => 'reddit',               'cat' => 'marketing', 'service' => 'Reddit Pixel'],
        ['match' => 'tiktok',               'cat' => 'marketing', 'service' => 'TikTok Pixel'],
        ['match' => 'pinterest',            'cat' => 'marketing', 'service' => 'Pinterest Tag'],
        ['match' => 'linkedin',             'cat' => 'marketing', 'service' => 'LinkedIn Insight Tag'],
    ];
}

/**
 * Mappa dei pattern per script inline.
 */
function cc_inline_map() {
    return [
        ['match' => 'googletagmanager', 'cat' => 'analytics', 'service' => 'Google Tag Manager'],
        ['match' => 'dataLayer.push',   'cat' => 'analytics', 'service' => 'Google Tag Manager'],
        ['match' => 'gtag(',            'cat' => 'analytics', 'service' => 'Google Analytics'],
        ['match' => 'ga(',              'cat' => 'analytics', 'service' => 'Google Analytics'],
        ['match' => 'clarity(',         'cat' => 'analytics', 'service' => 'Microsoft Clarity'],
        ['match' => '_paq.push',        'cat' => 'analytics', 'service' => 'Matomo'],
        ['match' => 'plausible(',       'cat' => 'analytics', 'service' => 'Plausible'],
        ['match' => 'umami.',           'cat' => 'analytics', 'service' => 'Umami'],
        ['match' => 'fbq(',             'cat' => 'marketing', 'service' => 'Meta Pixel'],
        ['match' => '_fbq',             'cat' => 'marketing', 'service' => 'Meta Pixel'],
        ['match' => 'ttq.',             'cat' => 'marketing', 'service' => 'TikTok Pixel'],
        ['match' => 'snaptr(',          'cat' => 'marketing', 'service' => 'Snapchat Pixel'],
        ['match' => 'pintrk(',          'cat' => 'marketing', 'service' => 'Pinterest Tag'],
        ['match' => 'twq(',             'cat' => 'marketing', 'service' => 'Twitter Pixel'],
        ['match' => 'lintrk(',          'cat' => 'marketing', 'service' => 'LinkedIn Insight Tag'],
        ['match' => 'hj(',              'cat' => 'marketing', 'service' => 'Hotjar'],
    ];
}

function cc_detect($haystack, $map = null) {
    $haystack = strtolower((string) $haystack);
    if ($haystack === '') return null;
    $map = $map ?: cc_service_map();
    foreach ($map as $row) {
        if (strpos($haystack, $row['match']) !== false) {
            return ['cat' => $row['cat'], 'service' => $row['service']];
        }
    }
    return null;
}

function cc_is_consent_mode_service($service) {
    return in_array($service, ['Google Tag Manager', 'Google Analytics', 'Google Ads'], true);
}

function cc_to_managed_tag($tag, $cat, $service) {
    $tag = preg_replace('/\stype=(["'])[^'"]*\1/i', '', $tag);
    $attrs = 'type="text/plain" data-category="' . esc_attr($cat) . '"';
    if ($service) {
        $attrs .= ' data-service="' . esc_attr($service) . '"';
    }
    return preg_replace('/<script/i', '<script ' . $attrs, $tag, 1);
}

/* ==========================================================================
   1. DEBUGGER (raccolta dati)
   ========================================================================== */
function cc_debug_enabled() {
    return CC_DEBUG && !is_admin() && is_user_logged_in() && current_user_can('manage_options');
}

function cc_debug_add($entry) {
    if (!cc_debug_enabled()) return;
    global $cc_debug_log;
    if (!is_array($cc_debug_log)) { $cc_debug_log = []; }
    $cc_debug_log[] = $entry;
}

function cc_debug_short($url, $max = 64) {
    $s = (string) $url;
    if ($s === '') return '';
    $p = wp_parse_url($s);
    if (!empty($p['path'])) {
        $s = ltrim($p['path'], '/');
        if (!empty($p['query'])) { $s .= '?' . $p['query']; }
    }
    if (strlen($s) > $max) { $s = '…' . substr($s, -$max); }
    return $s;
}

/* ==========================================================================
   2. GOOGLE CONSENT MODE v2 — Default "Denied"
   ========================================================================== */
add_action('wp_head', 'cc_consent_mode_default', 1);
function cc_consent_mode_default() {
    if (is_admin() || !CC_CONSENT_MODE) return;
    $region = CC_CM_REGIONS;
    ?>
<script data-cc-skip>
  window.dataLayer = window.dataLayer || [];
  function gtag(){ dataLayer.push(arguments); }
  gtag('consent', 'default', {
    'ad_storage': 'denied',
    'ad_user_data': 'denied',
    'ad_personalization': 'denied',
    'analytics_storage': 'denied',
    'functionality_storage': 'granted',
    'security_storage': 'granted',
    'wait_for_update': 500<?php if (!empty($region)) {
        echo ",
    'region': " . wp_json_encode(array_values($region));
    } ?>
  });
  gtag('set', 'ads_data_redaction', true);
  gtag('set', 'url_passthrough', true);
</script>
    <?php
}

/* ==========================================================================
   3a. BLOCCO SCRIPT ENQUEUED (script_loader_tag)
   ========================================================================== */
add_filter('script_loader_tag', 'cc_block_enqueued_scripts', 99, 3);
function cc_block_enqueued_scripts($tag, $handle, $src) {
    if (is_admin()) return $tag;
    if (strpos($tag, 'data-category=') !== false) return $tag;

    $detected = cc_detect($handle . ' ' . $src);
    if (!$detected) return $tag;

    if (CC_CONSENT_MODE && cc_is_consent_mode_service($detected['service'])) {
        cc_debug_add([
            'source'  => 'enqueued',
            'label'   => (string) $handle,
            'src'     => cc_debug_short($src),
            'cat'     => $detected['cat'],
            'service' => $detected['service'],
            'action'  => 'consent-mode',
        ]);
        return $tag;
    }

    cc_debug_add([
        'source'  => 'enqueued',
        'label'   => (string) $handle,
        'src'     => cc_debug_short($src),
        'cat'     => $detected['cat'],
        'service' => $detected['service'],
        'action'  => 'blocked',
    ]);

    return cc_to_managed_tag($tag, $detected['cat'], $detected['service']);
}

/* ==========================================================================
   3b. BLOCCO SCRIPT INLINE / HARDCODED (Output Buffering)
   ========================================================================== */
add_action('template_redirect', 'cc_start_output_buffer');
function cc_start_output_buffer() {
    if (is_admin() || is_feed() || wp_doing_ajax()
        || (defined('REST_REQUEST') && REST_REQUEST)
        || (defined('DOING_CRON') && DOING_CRON)) {
        return;
    }
    ob_start('cc_rewrite_inline_scripts');
}

function cc_rewrite_inline_scripts($html) {
    if (!is_string($html) || stripos($html, '<script') === false) {
        return $html;
    }

    return preg_replace_callback(
        '#<script\b([^>]*)>(.*?)</script>#is',
        function ($m) {
            $attrs   = $m[1];
            $content = $m[2];
            $tag     = $m[0];
            $lower   = strtolower($attrs . ' ' . $content);

            if (strpos($lower, 'data-category=') !== false) return $tag;
            if (strpos($lower, 'cookieconsent') !== false)   return $tag;
            if (strpos($lower, 'cc-main') !== false)         return $tag;
            if (strpos($lower, 'data-cc-skip') !== false) {
                cc_debug_add(['source' => 'inline', 'label' => '(script CC)', 'src' => '', 'cat' => '', 'service' => '', 'action' => 'skipped']);
                return $tag;
            }

            if (preg_match('/\bsrc\s*=\s*(["'])(.*?)\1/i', $attrs, $src)) {
                $detected = cc_detect($attrs . ' ' . $src[2]);
                if (!$detected) return $tag;

                if (CC_CONSENT_MODE && cc_is_consent_mode_service($detected['service'])) {
                    cc_debug_add(['source' => 'inline-src', 'label' => cc_debug_short($src[2]), 'src' => cc_debug_short($src[2]), 'cat' => $detected['cat'], 'service' => $detected['service'], 'action' => 'consent-mode']);
                    return $tag;
                }

                cc_debug_add(['source' => 'inline-src', 'label' => cc_debug_short($src[2]), 'src' => cc_debug_short($src[2]), 'cat' => $detected['cat'], 'service' => $detected['service'], 'action' => 'blocked']);
                return cc_to_managed_tag($tag, $detected['cat'], $detected['service']);
            }

            $detected = cc_detect($content, cc_inline_map());
            if (!$detected) return $tag;

            if (CC_CONSENT_MODE && cc_is_consent_mode_service($detected['service'])) {
                cc_debug_add(['source' => 'inline', 'label' => '(inline ' . $detected['service'] . ')', 'src' => '', 'cat' => $detected['cat'], 'service' => $detected['service'], 'action' => 'consent-mode']);
                return $tag;
            }

            cc_debug_add(['source' => 'inline', 'label' => '(inline ' . $detected['service'] . ')', 'src' => '', 'cat' => $detected['cat'], 'service' => $detected['service'], 'action' => 'blocked']);

            $clean = preg_replace('/\stype=(["'])[^'"]*\1/i', '', $attrs);
            return '<script type="text/plain" data-category="' . esc_attr($detected['cat'])
                . '" data-service="' . esc_attr($detected['service']) . '"'
                . $clean . '>' . $content . '</script>';
        },
        $html
    );
}

/* ==========================================================================
   4. RISOLUZIONE ASSET (Locale con fallback CDN)
   ========================================================================== */
function cc_asset($file) {
    $rel    = 'cookieconsent/' . $file;
    $child  = get_stylesheet_directory() . '/' . $rel;
    $parent = get_template_directory() . '/' . $rel;

    if (file_exists($child)) {
        return ['url' => get_stylesheet_directory_uri() . '/' . $rel, 'integrity' => ''];
    }
    if (file_exists($parent)) {
        return ['url' => get_template_directory_uri() . '/' . $rel, 'integrity' => ''];
    }

    $is_css = (strpos($file, '.css') !== false);
    return [
        'url'       => 'https://cdn.jsdelivr.net/gh/orestbida/cookieconsent@' . CC_VERSION . '/dist/' . $file,
        'integrity' => $is_css ? CC_SRI_CSS : CC_SRI_JS,
    ];
}

/* ==========================================================================
   5. BANNER COOKIECONSENT V3 + STILI GENERICI SYSTEM-FONT
   ========================================================================== */
add_action('wp_head', 'cc_render_head', 10);
function cc_render_head() {
    if (is_admin()) return;

    $css = cc_asset('cookieconsent.css');
    $js  = cc_asset('cookieconsent.umd.js');
    ?>
<link rel="stylesheet" href="<?php echo esc_url($css['url']); ?>"<?php
    echo $css['integrity'] ? ' integrity="' . esc_attr($css['integrity']) . '" crossorigin="anonymous"' : ''; ?>>
<script defer src="<?php echo esc_url($js['url']); ?>"<?php
    echo $js['integrity'] ? ' integrity="' . esc_attr($js['integrity']) . '" crossorigin="anonymous"' : ''; ?>></script>

<style>
  /* ---- Colori Banner: palette neutra e moderna ---- */
  #cc-main {
    --cc-bg: #ffffff !important;
    --cc-modal-bg: #ffffff !important;
    --cc-primary-color: #1e293b !important;
    --cc-secondary-color: #64748b !important;
    --cc-btn-primary-bg: #2563eb !important;
    --cc-btn-primary-color: #ffffff !important;
    --cc-btn-primary-hover-bg: #1d4ed8 !important;
    --cc-btn-primary-hover-color: #ffffff !important;
    --cc-btn-secondary-bg: #f1f5f9 !important;
    --cc-btn-secondary-color: #334155 !important;
    --cc-btn-secondary-hover-bg: #e2e8f0 !important;
    --cc-btn-secondary-hover-color: #0f172a !important;
    --cc-toggle-on-bg: #2563eb !important;
    --cc-separator-border-color: #f1f5f9 !important;
    --cc-cookie-category-block-bg: #f8fafc !important;
    --cc-cookie-category-block-hover-bg: #f1f5f9 !important;
    --cc-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
  }

  /* ---- Pulsante Flottante (basso-destra) ---- */
  #cc-rewrite-btn {
    all: unset !important;
    position: fixed !important;
    bottom: 20px !important;
    right: 20px !important;
    left: auto !important;
    z-index: 9999999 !important;
    width: 44px !important;
    height: 44px !important;
    border-radius: 50% !important;
    background-color: #1e293b !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
    box-sizing: border-box !important;
    transition: transform 0.2s ease, background-color 0.2s ease !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
  }
  #cc-rewrite-btn:hover {
    background-color: #0f172a !important;
    transform: scale(1.08) !important;
  }
  #cc-rewrite-btn:active { border: none !important; }
  #cc-rewrite-btn:focus { outline: none !important; }
  #cc-rewrite-btn:focus-visible {
    outline: 3px solid #2563eb !important;
    outline-offset: 3px !important;
  }
  #cc-rewrite-btn svg {
    width: 22px !important;
    height: 22px !important;
    fill: none !important;
    stroke: #ffffff !important;
    stroke-width: 2px !important;
    stroke-linecap: round !important;
    stroke-linejoin: round !important;
    display: block !important;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof CookieConsent === 'undefined') {
        console.warn('[CookieConsent] libreria non caricata: banner non attivo.');
        var btn = document.getElementById('cc-rewrite-btn');
        if (btn) { btn.style.display = 'none'; }
        return;
    }

    var domainRoot = '.' + window.location.hostname.replace(/^www\./, '');

    function pushConsentConsentMode() {
        if (typeof gtag !== 'function') return;
        var analytics = CookieConsent.acceptedCategory('analytics');
        var marketing = CookieConsent.acceptedCategory('marketing');
        gtag('consent', 'update', {
            'analytics_storage':   analytics ? 'granted' : 'denied',
            'ad_storage':          marketing ? 'granted' : 'denied',
            'ad_user_data':        marketing ? 'granted' : 'denied',
            'ad_personalization': marketing ? 'granted' : 'denied'
        });
    }

    CookieConsent.run({
        autoClearCookies: true,
        manageScriptTags: true,

        onConsent: pushConsentConsentMode,
        onChange:  pushConsentConsentMode,

        categories: {
            necessary: { enabled: true, readOnly: true },
            analytics: {
                enabled: false,
                autoClear: {
                    cookies: [
                        { name: /^_ga/,  path: '/', domain: domainRoot },
                        { name: /^_gid/, path: '/', domain: domainRoot },
                        { name: /^_gat/, path: '/', domain: domainRoot },
                        { name: /^_cl/,  path: '/', domain: domainRoot },
                        { name: /^_pk_/, path: '/', domain: domainRoot },
                        { name: /^ph_/,  path: '/', domain: domainRoot },
                        { name: /^_hj/,  path: '/', domain: domainRoot }
                    ]
                }
            },
            marketing: {
                enabled: false,
                autoClear: {
                    cookies: [
                        { name: /^_fbp/, path: '/', domain: domainRoot },
                        { name: /^_fbc/, path: '/', domain: domainRoot },
                        { name: /^_gcl/, path: '/', domain: domainRoot },
                        { name: 'IDE',   path: '/', domain: domainRoot },
                        { name: /^_uet/, path: '/', domain: domainRoot },
                        { name: /^_pin_/,path: '/', domain: domainRoot },
                        { name: /^_ttp/, path: '/', domain: domainRoot },
                        { name: /^_scid/,path: '/', domain: domainRoot },
                        { name: 'li_fat_id', path: '/', domain: domainRoot }
                    ]
                }
            }
        },

        language: {
            default: 'it',
            translations: {
                it: {
                    consentModal: {
                        title: 'Informativa sui Cookie',
                        description: 'Utilizziamo i cookie per garantire il funzionamento del sito e per eventuali analisi del traffico o marketing.',
                        acceptAllBtn: 'Accetta tutti',
                        acceptNecessaryBtn: 'Rifiuta tutti',
                        showPreferencesBtn: 'Personalizza'
                    },
                    preferencesModal: {
                        title: 'Gestione Preferenze Cookie',
                        acceptAllBtn: 'Accetta tutti',
                        acceptNecessaryBtn: 'Rifiuta tutti',
                        savePreferencesBtn: 'Salva preferenze',
                        closeIconLabel: 'Chiudi',
                        serviceCounterLabel: 'Servizi',
                        sections: [
                            { title: 'Strettamente Necessari', description: 'Cookie tecnici indispensabili per il funzionamento del sito.', linkedCategory: 'necessary' },
                            { title: 'Statistiche e Analisi', description: 'Cookie per comprendere l'utilizzo del sito in forma aggregata.', linkedCategory: 'analytics' },
                            { title: 'Marketing e Profilazione', description: 'Cookie per la personalizzazione dei contenuti pubblicitari.', linkedCategory: 'marketing' }
                        ]
                    }
                }
            }
        }
    });
});
</script>
    <?php
}

/* ==========================================================================
   6. PULSANTE FLUTTUANTE NEL FOOTER
   ========================================================================== */
add_action('wp_footer', 'cc_render_footer_button', 10);
function cc_render_footer_button() {
    if (is_admin()) return;
    ?>
<button type="button" id="cc-rewrite-btn" data-cc-skip
        data-cc="show-preferencesModal"
        title="Gestisci Preferenze Cookie" aria-label="Gestisci Preferenze Cookie">
  <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    <path d="M12 2a10 10 0 1 0 10 10 3.5 3.5 0 0 1-4.5-4.5A3.5 3.5 0 0 1 12 2z" />
    <circle cx="8.5" cy="8.5" r="1" />
    <circle cx="7" cy="14.5" r="1" />
    <circle cx="12" cy="15.5" r="1" />
    <circle cx="15" cy="11" r="1" />
  </svg>
</button>
    <?php
}

/* ==========================================================================
   7. DEBUGGER (PANNELLO ADMIN ATTIVATO)
   ========================================================================== */
add_action('wp_footer', 'cc_render_debug_panel', 999);
function cc_render_debug_panel() {
    if (!cc_debug_enabled()) {
        return;
    }

    global $cc_debug_log;
    $log     = is_array($cc_debug_log) ? $cc_debug_log : [];
    $blocked = 0;
    $cmode   = 0;
    foreach ($log as $row) {
        if (($row['action'] ?? '') === 'blocked')      $blocked++;
        if (($row['action'] ?? '') === 'consent-mode') $cmode++;
    }
    ?>
<div id="cc-debug" data-cc-skip>
  <div id="cc-debug-panel" data-cc-skip hidden>
    <div id="cc-debug-summary" data-cc-skip></div>
    <div id="cc-debug-table" data-cc-skip></div>
  </div>
  <button type="button" id="cc-debug-toggle" data-cc-skip>
    🐞 CC Debug
    <span class="cc-badge cc-badge-blocked"><?php echo (int) $blocked; ?></span>
    <span class="cc-badge cc-badge-cmode"><?php echo (int) $cmode; ?></span>
  </button>
</div>
<style>
  #cc-debug{position:fixed;bottom:20px;left:20px;z-index:2147483000;font:12px/1.45 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;color:#e5e7eb}
  #cc-debug-toggle{all:unset;cursor:pointer;background:#111827;color:#e5e7eb;padding:6px 10px;border-radius:6px;box-shadow:0 4px 10px rgba(0,0,0,.3);display:inline-flex;align-items:center;gap:6px}
  #cc-debug-toggle:hover{background:#1f2937}
  .cc-badge{border-radius:10px;padding:0 6px;font-size:11px;font-weight:700}
  .cc-badge-blocked{background:#dc2626;color:#fff}
  .cc-badge-cmode{background:#d97706;color:#fff}
  #cc-debug-panel{margin-bottom:8px;background:#0b1220;border:1px solid #334155;border-radius:8px;padding:10px;width:min(92vw,660px);max-height:60vh;overflow:auto;box-shadow:0 10px 30px rgba(0,0,0,.45)}
  #cc-debug-panel table{border-collapse:collapse;width:100%;margin-top:8px}
  #cc-debug-panel th,#cc-debug-panel td{text-align:left;padding:3px 6px;border-bottom:1px solid #1f2937;vertical-align:top;word-break:break-word}
  #cc-debug-panel th{color:#93c5fd;font-weight:600;position:sticky;top:0;background:#0b1220}
  #cc-debug .ok{color:#34d399}#cc-debug .bad{color:#f87171}#cc-debug .warn{color:#fbbf24}#cc-debug .muted{color:#9ca3af}
  #cc-debug-summary div{margin:2px 0}
</style>
<script data-cc-skip>
(function () {
  var LOG          = <?php echo wp_json_encode($log); ?>;
  var CONSENT_MODE = <?php echo CC_CONSENT_MODE ? 'true' : 'false'; ?>;
  var CM_REGIONS   = <?php echo wp_json_encode(array_values(CC_CM_REGIONS)); ?>;

  var panel   = document.getElementById('cc-debug-panel');
  var toggle  = document.getElementById('cc-debug-toggle');
  var summary = document.getElementById('cc-debug-summary');
  var table   = document.getElementById('cc-debug-table');
  var timer   = null;

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }
  function tag(v) { return '<span class="' + v + '">'; }

  function consentState() {
    if (typeof CookieConsent === 'undefined') return null;
    try {
      return {
        analytics: CookieConsent.acceptedCategory('analytics'),
        marketing: CookieConsent.acceptedCategory('marketing')
      };
    } catch (e) { return null; }
  }

  function renderSummary() {
    var st = consentState();
    var html = '';
    html += '<div><b>Consent Mode v2:</b> ' + (CONSENT_MODE ? tag('ok') + 'ON' : tag('warn') + 'OFF') + '</span>'
          + (CM_REGIONS.length ? ' (region: ' + esc(CM_REGIONS.join(', ')) + ')' : ' (globale)') + '</div>';
    html += '<div><b>gtag():</b> ' + (typeof gtag === 'function' ? tag('ok') + 'definito' : tag('warn') + 'assente') + '</span></div>';
    html += '<div><b>CookieConsent:</b> ' + (typeof CookieConsent !== 'undefined' ? tag('ok') + 'caricato' : tag('bad') + 'NON caricato') + '</span></div>';
    if (st) {
      html += '<div><b>analytics_storage:</b> ' + (st.analytics ? tag('ok') + 'granted' : tag('bad') + 'denied') + '</span></div>';
      html += '<div><b>ad_storage / ad_user_data / ad_personalization:</b> ' + (st.marketing ? tag('ok') + 'granted' : tag('bad') + 'denied') + '</span></div>';
    } else {
      html += '<div class="muted">Stato consenso non disponibile (libreria assente).</div>';
    }
    summary.innerHTML = html;
  }

  function renderTable() {
    if (!LOG.length) { table.innerHTML = '<em class="muted">Nessuno script rilevato.</em>'; return; }
    var html = '<table><thead><tr>'
      + '<th>Origine</th><th>Handle / File</th><th>Categoria</th><th>Servizio</th><th>Azione</th>'
      + '</tr></thead><tbody>';
    LOG.forEach(function (r) {
      var cls = r.action === 'blocked' ? 'bad'
              : r.action === 'consent-mode' ? 'warn'
              : 'muted';
      html += '<tr>'
        + '<td class="muted">' + esc(r.source) + '</td>'
        + '<td title="' + esc(r.src) + '">' + esc(r.label || r.src) + '</td>'
        + '<td>' + esc(r.cat) + '</td>'
        + '<td>' + esc(r.service) + '</td>'
        + '<td class="' + cls + '">' + esc(r.action) + '</td>'
        + '</tr>';
    });
    table.innerHTML = html + '</tbody></table>';
  }

  function render() { renderSummary(); renderTable(); }

  toggle.addEventListener('click', function () {
    if (panel.hasAttribute('hidden')) {
      panel.removeAttribute('hidden');
      render();
      timer = setInterval(render, 1000);
    } else {
      panel.setAttribute('hidden', '');
      if (timer) { clearInterval(timer); timer = null; }
    }
  });
})();
</script>
    <?php
}
