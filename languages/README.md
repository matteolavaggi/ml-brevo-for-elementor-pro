# ML Brevo for Elementor Pro - Traduzioni

Questo plugin supporta le traduzioni multilingua nelle seguenti lingue:

## Lingue Supportate

- 🇮🇹 **Italiano** (`it_IT`) - Traduzione completa
- 🇫🇷 **Francese** (`fr_FR`) - Traduzione completa  
- 🇩🇪 **Tedesco** (`de_DE`) - Traduzione completa
- 🇪🇸 **Spagnolo** (`es_ES`) - Traduzione completa

## Come Funziona

Il plugin rileva automaticamente la lingua del tuo sito WordPress e carica la traduzione appropriata. Le traduzioni vengono caricate automaticamente quando il plugin viene attivato.

## File di Traduzione

- `ml-brevo-for-elementor-pro.pot` - File template per nuove traduzioni
- `ml-brevo-for-elementor-pro-it_IT.po` - Traduzione italiana
- `ml-brevo-for-elementor-pro-fr_FR.po` - Traduzione francese
- `ml-brevo-for-elementor-pro-de_DE.po` - Traduzione tedesca
- `ml-brevo-for-elementor-pro-es_ES.po` - Traduzione spagnola

## Aggiungere Nuove Traduzioni

Per aggiungere una nuova lingua:

1. Copia il file `ml-brevo-for-elementor-pro.pot`
2. Rinominalo usando il codice della lingua (es: `ml-brevo-for-elementor-pro-pt_BR.po` per il portoghese brasiliano)
3. Traduci tutte le stringhe `msgstr ""`
4. Salva il file nella cartella `languages/`

## Strumenti Consigliati

- **Poedit** - Editor grafico per file PO
- **Loco Translate** - Plugin WordPress per tradurre direttamente dall'admin
- **WPML** - Plugin multilingua per WordPress

## Testare le Traduzioni

1. Cambia la lingua del tuo sito WordPress in **Impostazioni > Generali > Lingua del sito**
2. Vai nella sezione Elementor Forms e aggiungi un'azione Brevo
3. Verifica che tutte le etichette siano tradotte correttamente

## Compilare i File MO

Per migliori performance, compila i file `.po` in `.mo`:

```bash
msgfmt ml-brevo-for-elementor-pro-it_IT.po -o ml-brevo-for-elementor-pro-it_IT.mo
msgfmt ml-brevo-for-elementor-pro-fr_FR.po -o ml-brevo-for-elementor-pro-fr_FR.mo
msgfmt ml-brevo-for-elementor-pro-de_DE.po -o ml-brevo-for-elementor-pro-de_DE.mo
msgfmt ml-brevo-for-elementor-pro-es_ES.po -o ml-brevo-for-elementor-pro-es_ES.mo
```

## Supporto

Per problemi con le traduzioni o per contribuire con nuove lingue, contatta: **info@matteolavaggi.it** 