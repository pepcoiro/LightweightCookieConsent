# 🍪 WordPress Zero-Plugin Cookie Consent & Script Blocker

Un'soluzione ultraleggera e pronta all'uso per gestire il consenso cookie e il blocco preventivo degli script (GDPR/CCPA compliant) su WordPress, **senza installare plugin pesanti**.

Basato sulla potente libreria open source [CookieConsent v3](https://github.com/orestbida/cookieconsent).

---

## ✨ Caratteristiche

- 🚀 **Zero Plugin:** Inserisci un singolo file o uno snippet di codice PHP nel tuo tema.
- 🛡️ **Blocco Preventivo Automatico:** Intercetta e blocca automaticamente gli script di terze parti (Google Analytics, Facebook Pixel, Clarity, ecc.) prima che l'utente dia il consenso.
- 🎨 **100% Personalizzabile:** CSS basato su variabili facilmente adattabili ai colori del tuo tema.
- 🍪 **Floating Re-open Button:** Pulsante fluttuante minimale (icona biscotto SVG) in basso a destra per permettere agli utenti di modificare le preferenze in qualsiasi momento.
- 🧹 **Auto-Cleaning Cookie:** Cancella automaticamente i cookie salvati (es. `_ga`, `_fbp`) se l'utente revoca il consenso.

---

## 🛠️ Installazione

Puoi utilizzare questo codice in due modi differenti:

### Opzione A: Tramite Code Snippets (Consigliata)
1. Installa e attiva un plugin per snippet di codice (es. *Code Snippets* o *WPCode*).
2. Crea un nuovo snippet **PHP**.
3. Incolla il codice contenuto nel file `wp-cookie-consent.php`.
4. Imposta l'esecuzione su **"Esegui ovunque" (Run Everywhere)** e salva.

### Opzione B: Tramite file `functions.php`
1. Apri il file `functions.php` del tuo Tema Child.
2. Incolla il codice in fondo al file.

---

## 🎨 Personalizzazione

### 1. Colori e Stile
All'interno del blocco `<style>`, puoi modificare i valori delle variabili CSS `:root` per adattare il banner ai colori del tuo brand:

```css
:root {
  --cc-custom-bg: #ffffff;           /* Sfondo modale */
  --cc-custom-text-primary: #222222; /* Testo principale */
  --cc-custom-accent: #0073aa;       /* Colore primario del brand */
  --cc-custom-accent-hover: #005177; /* Colore stato hover */
}
