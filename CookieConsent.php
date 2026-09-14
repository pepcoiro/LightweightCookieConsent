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
   1. BLOCCO PREVENTIVO AUTOMATICO SCRIPT (ANALITYCS & MARKETING)
   ========================================================================== */
add_filter('script_loader_tag', function($tag, $handle, $src) {
    // Non eseguire il blocco nel pannello di amministrazione
    if (is_admin()) {
        return $tag;
    }

    // Parole chiave nei file JS da assegnare alla categoria "analytics"
    $analytics_keywords = ['google-analytics', 'gtag', 'clarity', 'rudderstack', 'matomo'];

    // Parole chiave nei file JS da assegnare alla categoria "marketing"
    $marketing_keywords = ['facebook', 'fbq', 'google-ads', 'bing', 'hubspot', 'reddit', 'tiktok', 'pinterest'];

    $search_string = strtolower($handle . ' ' . $src);
    $category = null;

    foreach ($analytics_keywords as $term) {
        if (strpos($search_string, $term) !== false) {
            $category = 'analytics';
            break;
        }
    }

    if (!$category) {
        foreach ($marketing_keywords as $term) {
            if (strpos($search_string, $term) !== false) {
                $category = 'marketing';
                break;
            }
        }
    }

    // Se lo script rientra tra quelli da bloccare, modifica il tag HTML
    if ($category) {
        // Rimuove eventuali attributi type preesistenti
        $tag = preg_replace('/type=(["\'])[^"\']+\1/i', '', $tag);
        // Sostituisce il tag <script con type="text/plain" e il relativo data-category
        $tag = preg_replace('/<script/i', '<script type="text/plain" data-category="' . esc_attr($category) . '"', $tag, 1);
    }

    return $tag;
}, 99, 3);


/* ==========================================================================
   2. INIZIALIZZAZIONE BANNER COOKIECONSENT V3 & CSS
   ========================================================================== */
add_action('wp_head', function() {
    if (is_admin()) return;
?>
<!-- CookieConsent v3 CSS & JS da CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orestbida/cookieconsent@v3.0.0/dist/cookieconsent.css">
<script defer src="https://cdn.jsdelivr.net/gh/orestbida/cookieconsent@v3.0.0/dist/cookieconsent.umd.js"></script>

<style>
  /* =========================================================
     CONFIGURAZIONE COLORI E TEMA BANNER
     (Modifica i valori hex di seguito per adattarli al tuo tema)
     ========================================================= */
  :root {
    --cc-custom-bg: #ffffff;
    --cc-custom-text-primary: #222222;
    --cc-custom-text-secondary: #555555;
    --cc-custom-accent: #0073aa;         /* Colore primario (pulsante accetta) */
    --cc-custom-accent-hover: #005177;   /* Colore hover primario */
    --cc-custom-btn-sec-bg: #f4f4f4;     /* Sfondo pulsanti secondari */
    --cc-custom-btn-sec-text: #222222;   /* Testo pulsanti secondari */
  }

  #cc-main {
    --cc-bg: var(--cc-custom-bg) !important;
    --cc-modal-bg: var(--cc-custom-bg) !important;
    --cc-primary-color: var(--cc-custom-text-primary) !important;
    --cc-secondary-color: var(--cc-custom-text-secondary) !important;
    
    /* Pulsante Primario */
    --cc-btn-primary-bg: var(--cc-custom-accent) !important;
    --cc-btn-primary-color: #ffffff !important;
    --cc-btn-primary-hover-bg: var(--cc-custom-accent-hover) !important;
    --cc-btn-primary-hover-color: #ffffff !important;

    /* Pulsanti Secondari (Rifiuta / Personalizza) */
    --cc-btn-secondary-bg: var(--cc-custom-btn-sec-bg) !important;
    --cc-btn-secondary-color: var(--cc-custom-btn-sec-text) !important;
    --cc-btn-secondary-hover-bg: var(--cc-custom-accent) !important;
    --cc-btn-secondary-hover-color: #ffffff !important;

    /* Toggle & Bordi */
    --cc-toggle-on-bg: var(--cc-custom-accent) !important;
    --cc-separator-border-color: #e5e5e5 !important;
    --cc-cookie-category-block-bg: #f9f9f9 !important;
    --cc-cookie-category-block-hover-bg: #f0f0f0 !important;
  }

  /* =========================================================
     PULSANTE FLUTTUANTE RIAPERTURA PREFERENZE
     ========================================================= */
  #cc-rewrite-btn {
    all: unset !important;
    position: fixed !important;
    bottom: 20px !important;
    right: 20px !important; /* Cambia in 'left: 20px !important;' per posizionarlo a sinistra */
    left: auto !important;
    z-index: 999999 !important;
    width: 44px !important;
    height: 44px !important;
    border-radius: 50% !important;
    background-color: var(--cc-custom-accent) !important;
    cursor: pointer !important;    
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
    outline: none !important;
    box-sizing: border-box !important;
    transition: transform 0.2s ease, background-color 0.2s ease !important;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15) !important;
  }

  #cc-rewrite-btn:hover {
    background-color: var(--cc-custom-accent-hover) !important;
    transform: scale(1.08) !important;
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
    // Dominio dinamico per la pulizia dei cookie
    var domainRoot = '.' + window.location.hostname.replace(/^www\./, '');

    CookieConsent.run({
        autoClearCookies: true,
        manageScriptTags: true, // Esegue automaticamente i tag modificati al consenso

        categories: {
            necessary: { enabled: true, readOnly: true },
            analytics: {
                enabled: false,
                autoClear: {
                    cookies: [
                        { name: /^_ga/, path: '/', domain: domainRoot },
                        { name: /^_cl/, path: '/', domain: domainRoot }
                    ]
                }
            },
            marketing: {
                enabled: false,
                autoClear: {
                    cookies: [
                        { name: /^_fbp/, path: '/', domain: domainRoot },
                        { name: /^_gcl/, path: '/', domain: domainRoot }
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
                        description: 'Utilizziamo i cookie per garantire il funzionamento del sito e, previo tuo consenso, per analizzare il traffico o personalizzare i contenuti.',
                        acceptAllBtn: 'Accetta tutti',
                        acceptNecessaryBtn: 'Rifiuta tutti',
                        showPreferencesBtn: 'Personalizza'
                    },
                    preferencesModal: {
                        title: 'Gestione Preferenze Cookie',
                        acceptAllBtn: 'Accetta tutti',
                        acceptNecessaryBtn: 'Rifiuta tutti',
                        savePreferencesBtn: 'Salva preferenze',
                        sections: [
                            { title: 'Strettamente Necessari', description: 'Cookie tecnici indispensabili per il corretto funzionamento del sito.', linkedCategory: 'necessary' },
                            { title: 'Statistiche e Analisi', description: 'Cookie utilizzati per raccogliere dati aggregati sul comportamento degli utenti.', linkedCategory: 'analytics' },
                            { title: 'Marketing e Profilazione', description: 'Cookie per tracciare i visitatori attraverso i siti web per mostrare annunci pertinenti.', linkedCategory: 'marketing' }
                        ]
                    }
                }
            }
        }
    });
});
</script>
<?php
}, 10);


/* ==========================================================================
   3. PULSANTE FLUTTUANTE IN FOOTER
   ========================================================================== */
add_action('wp_footer', function() {
    if (is_admin()) return;
?>
<button type="button" id="cc-rewrite-btn" data-cc="show-preferencesModal" title="Gestisci Preferenze Cookie" aria-label="Gestisci Preferenze Cookie">
  <svg viewBox="0 0 24 24">
    <path d="M12 2a10 10 0 1 0 10 10 3.5 3.5 0 0 1-4.5-4.5A3.5 3.5 0 0 1 12 2z" />
    <circle cx="8.5" cy="8.5" r="1" />
    <circle cx="7" cy="14.5" r="1" />
    <circle cx="12" cy="15.5" r="1" />
    <circle cx="15" cy="11" r="1" />
  </svg>
</button>
<?php
});
