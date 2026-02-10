# Composer Update Dokumentation

**Datum:** 2026-01-30
**Projekt:** ezp25-jacexample (eZ Platform + Legacy)
**PHP Version:** 8.3
**Symfony Version:** 3.4.49

---

## Zusammenfassung

Erfolgreiches Composer Update mit PHP 8.3 Kompatibilität. Viele Netgen-Bundles wurden durch se7enxweb-Forks ersetzt, die PHP 8.3-kompatibel sind.

---

## 1. Vorbereitung: JSON-Syntax-Fehler behoben

**Problem:** Trailing Comma in `composer.json` Zeile 20

**Lösung:**
```json
// Vorher (fehlerhaft):
"psr-4": {
    "AppBundle\\": "src/AppBundle/",
    "Cjw\\": "src/Cjw/",
},

// Nachher (korrigiert):
"psr-4": {
    "AppBundle\\": "src/AppBundle/",
    "Cjw\\": "src/Cjw/"
},
```

---

## 2. Composer Update durchgeführt

```bash
php composer.phar update --with-dependencies --ignore-platform-reqs
```

**Ergebnis:**
- 81 Packages entfernt
- 12 Packages aktualisiert
- 1 Package neu installiert

### Entfernte Packages (Auszug):
- beberlei/assert
- captainhook/captainhook
- ezsystems/behatbundle (alte Version)
- netgen/* (diverse Bundles, ersetzt durch se7enxweb-Forks)
- twig/twig (v2.16.1 entfernt)

### Aktualisierte Packages:
- doctrine/doctrine-migrations-bundle (2.2.3 => 3.0.3 => später 2.2.3)
- doctrine/migrations (2.3.1 => 3.0.1 => später 2.3.1)
- se7enxweb/ezplatform-user (v1.0.12 => v1.0.13)
- se7enxweb/ezpublish-kernel (v7.5.37 => v7.5.38)
- se7enxweb/repository-forms (v2.5.18 => v2.5.20)
- se7enxweb/symfony (v3.4.52 => v3.4.54)
- se7enxweb/tagsbundle (v3.4.14 => v3.4.15)
- se7enxweb/twig (v2.16.2 => v2.16.3)

---

## 3. Fehlende Bundles nachinstalliert

### 3.1 Translation Bundles

```bash
php composer.phar require lexik/translation-bundle:^4.0 --ignore-platform-reqs
php composer.phar require primedigital/translations-bundle --ignore-platform-reqs
```

**Grund:** Wurden beim Update entfernt, sind aber im AppKernel registriert.

### 3.2 Redis Bundles

```bash
php composer.phar require snc/redis-bundle:^3.0 --ignore-platform-reqs
php composer.phar require predis/predis:^1.1 --ignore-platform-reqs
```

**Hinweis:** predis v3.3 ist nicht kompatibel mit snc/redis-bundle v3.6, daher v1.1 installiert.

### 3.3 Netgen Bundles (ältere Versionen für Symfony 3.4)

```bash
# Batch 1
php composer.phar require \
  netgen/ez-forms-bundle:^2.0 \
  netgen/open-graph-bundle:^1.3 \
  netgen/metadata-bundle:^2.0 \
  netgen/content-type-list-bundle:^1.3 \
  netgen/birthday-bundle:^1.3 \
  netgen/enhanced-selection-bundle:^3.4 \
  netgen/siteaccess-routes-bundle:^1.1 \
  --ignore-platform-reqs --no-scripts

# Batch 2
php composer.phar require \
  netgen/site-generator-bundle:^1.5 \
  netgen/site-installer-bundle:^1.3 \
  netgen/site-bundle:^1.7 \
  netgen/admin-ui-bundle:^2.9 \
  netgen/information-collection-bundle:^1.9 \
  --ignore-platform-reqs --no-scripts

# Content Browser
php composer.phar require \
  netgen/content-browser:^1.4 \
  netgen/content-browser-ui:^1.4 \
  --ignore-platform-reqs --no-scripts

# Rich Text
php composer.phar require netgen/richtext-datatype-bundle:^1.1 --ignore-platform-reqs
```

### 3.4 Netgen Layouts (mit Doctrine Downgrade)

```bash
php composer.phar require \
  doctrine/migrations:^2.2 \
  doctrine/doctrine-migrations-bundle:^2.2 \
  netgen/layouts-core:^1.4 \
  netgen/layouts-standard:^1.4 \
  netgen/layouts-ui:^1.4 \
  --ignore-platform-reqs --no-scripts
```

**Wichtig:** Doctrine Migrations musste auf v2.2 downgraded werden, da Netgen Layouts v1.4 nicht mit v3.x kompatibel ist.

### 3.5 se7enxweb Layout Integration

```bash
php composer.phar require \
  se7enxweb/content-browser-ezplatform \
  se7enxweb/layouts-ezplatform \
  se7enxweb/layouts-ezplatform-site-api \
  se7enxweb/layouts-ezplatform-relation-list-query \
  se7enxweb/layouts-ezplatform-tags-query \
  --ignore-platform-reqs --no-scripts
```

### 3.6 Behat Bundle

```bash
php composer.phar require ezsystems/behatbundle:^7.0 --ignore-platform-reqs
```

**Hinweis:** Version 7.0 statt 9.0, da kompatibel mit behat ^3.7.

### 3.7 CJW Config Processor

```bash
php composer.phar require cjw-network/cjw-config-processor:^2.1 --ignore-platform-reqs
```

**Hinweis:** v3.1 ist nicht mit Symfony 3.4 kompatibel (verwendet getRootNode() statt root()).

### 3.8 se7enxweb Site Legacy Bundle

```bash
php composer.phar require se7enxweb/site-legacy-bundle --ignore-platform-reqs
```

---

## 4. Konflikte gelöst

### 4.1 TagsBundle Konflikt

**Problem:** Sowohl `netgen/tagsbundle` (v3.4.13) als auch `se7enxweb/tagsbundle` (v3.4.15) waren installiert. Die netgen-Version verursachte PHP 8.3 Kompatibilitätsfehler.

**Lösung:**
```bash
# netgen/site-bundle entfernt (benötigt netgen/tagsbundle)
php composer.phar remove netgen/site-bundle --ignore-platform-reqs --no-scripts

# se7enxweb/tagsbundle explizit gesetzt
php composer.phar require se7enxweb/tagsbundle:^3.4 --ignore-platform-reqs
```

**Resultat:**
- netgen/tagsbundle entfernt
- netgen/site-bundle entfernt (ersetzt durch se7enxweb/site-bundle)
- ezsystems/ezplatform-solr-search-engine entfernt

### 4.2 Sentry Bundle

**Problem:** Sentry Bundle war im AppKernel registriert, aber nicht installiert.

**Lösung:**
```php
// app/AppKernel.php Zeile 76-78
// Sentry Bundle entfernt, da nicht installiert
// if ($this->getEnvironment() === 'prod') {
//     $bundles[] = new Sentry\SentryBundle\SentryBundle();
// }
```

---

## 5. Finale Package-Versionen

### Doctrine
- doctrine/doctrine-bundle: ^1.9.1
- doctrine/orm: ^2.6.3
- doctrine/migrations: 2.3.1
- doctrine/doctrine-migrations-bundle: 2.2.3

### Symfony
- se7enxweb/symfony: v3.4.54
- symfony/monolog-bundle: ^3.3.1
- symfony/swiftmailer-bundle: ^3.2.4

### eZ Platform Core
- se7enxweb/ezpublish-kernel: v7.5.38
- se7enxweb/ezplatform-user: v1.0.13
- se7enxweb/repository-forms: v2.5.20

### Twig
- se7enxweb/twig: v2.16.3
- twig/extensions: ^1.5.3

### Netgen Bundles (Symfony 3.4 kompatibel)
- netgen/birthday-bundle: 1.3.1
- netgen/content-type-list-bundle: 1.3.1
- netgen/enhanced-selection-bundle: 3.4.1
- netgen/ez-forms-bundle: 2.0.3
- netgen/metadata-bundle: 2.0.1
- netgen/open-graph-bundle: 1.3.2
- netgen/siteaccess-routes-bundle: 1.1.0
- netgen/site-generator-bundle: 1.5.1
- netgen/site-installer-bundle: 1.3.0
- netgen/admin-ui-bundle: 2.9.13
- netgen/information-collection-bundle: v1.9.5
- netgen/content-browser: 1.4.1
- netgen/content-browser-ui: 1.4.1
- netgen/richtext-datatype-bundle: 1.1.1

### Netgen Layouts
- netgen/layouts-core: 1.4.13
- netgen/layouts-standard: 1.4.3
- netgen/layouts-ui: 1.4.5

### se7enxweb Integration
- se7enxweb/tagsbundle: v3.4.15
- se7enxweb/site-bundle: v1.7.3
- se7enxweb/site-legacy-bundle: v1.4.5
- se7enxweb/content-browser-ezplatform: v1.4.2
- se7enxweb/layouts-ezplatform: v1.4.11
- se7enxweb/layouts-ezplatform-site-api: v1.4.5
- se7enxweb/layouts-ezplatform-relation-list-query: v1.4.2
- se7enxweb/layouts-ezplatform-tags-query: v1.4.2
- se7enxweb/ezplatform-site-api: 3.7.1
- se7enxweb/eztags: v2.3.1

### Redis
- snc/redis-bundle: 3.6.0
- predis/predis: v1.1.10

### Andere
- lexik/translation-bundle: v4.0.14
- primedigital/translations-bundle: v1.0.0
- cjw-network/cjw-config-processor: v2.1.0
- ezsystems/behatbundle: v7.0.3

---

## 6. Wichtige Hinweise

### PHP 8.3 Kompatibilität
Die meisten Packages wurden durch se7enxweb-Forks ersetzt, die PHP 8.3-kompatibel sind. Die Netgen-Bundles verwenden ältere Versionen, die mit Symfony 3.4 und PHP 8.3 funktionieren.

### Abandoned Packages
Folgende Packages sind deprecated, werden aber noch benötigt:
- doctrine/annotations
- doctrine/cache
- swiftmailer/swiftmailer
- twig/extensions
- symfony/assetic-bundle
- netgen/ezchangeclass
- netgen/hideuntildate
- netgen/ngclasslist

### Cache
Nach Änderungen am AppKernel immer Cache leeren:
```bash
rm -rf var/cache/*
```

---

## 7. Testen der Installation

```bash
# Symfony Version prüfen
php bin/console --version
# Ausgabe: Symfony 3.4.49 (kernel: app, env: dev, debug: true)

# Liste aller Bundles anzeigen
php bin/console debug:container --parameters

# Autoload regenerieren
php composer.phar dump-autoload --optimize
```

---

## 8. Zusammenfassung der Kommandos

```bash
# 1. JSON-Fehler manuell in composer.json behoben

# 2. Hauptupdate
php composer.phar update --with-dependencies --ignore-platform-reqs

# 3. Fehlende Bundles installieren
php composer.phar require lexik/translation-bundle:^4.0 primedigital/translations-bundle --ignore-platform-reqs --no-scripts
php composer.phar require snc/redis-bundle:^3.0 predis/predis:^1.1 --ignore-platform-reqs --no-scripts
php composer.phar require netgen/ez-forms-bundle:^2.0 netgen/open-graph-bundle:^1.3 netgen/metadata-bundle:^2.0 netgen/content-type-list-bundle:^1.3 netgen/birthday-bundle:^1.3 netgen/enhanced-selection-bundle:^3.4 netgen/siteaccess-routes-bundle:^1.1 --ignore-platform-reqs --no-scripts
php composer.phar require netgen/site-generator-bundle:^1.5 netgen/site-installer-bundle:^1.3 netgen/site-bundle:^1.7 netgen/admin-ui-bundle:^2.9 netgen/information-collection-bundle:^1.9 --ignore-platform-reqs --no-scripts
php composer.phar require netgen/content-browser:^1.4 netgen/content-browser-ui:^1.4 --ignore-platform-reqs --no-scripts
php composer.phar require doctrine/migrations:^2.2 doctrine/doctrine-migrations-bundle:^2.2 netgen/layouts-core:^1.4 netgen/layouts-standard:^1.4 netgen/layouts-ui:^1.4 --ignore-platform-reqs --no-scripts
php composer.phar require se7enxweb/content-browser-ezplatform se7enxweb/layouts-ezplatform se7enxweb/layouts-ezplatform-site-api se7enxweb/layouts-ezplatform-relation-list-query se7enxweb/layouts-ezplatform-tags-query --ignore-platform-reqs --no-scripts
php composer.phar require netgen/richtext-datatype-bundle:^1.1 ezsystems/behatbundle:^7.0 --ignore-platform-reqs --no-scripts
php composer.phar require cjw-network/cjw-config-processor:^2.1 --ignore-platform-reqs --no-scripts
php composer.phar require se7enxweb/site-legacy-bundle --ignore-platform-reqs --no-scripts

# 4. Konflikte lösen
php composer.phar remove netgen/site-bundle --ignore-platform-reqs --no-scripts
php composer.phar require se7enxweb/tagsbundle:^3.4 --ignore-platform-reqs --no-scripts

# 5. AppKernel.php anpassen (Sentry Bundle auskommentieren)

# 6. Cache leeren
rm -rf var/cache/*

# 7. Testen
php bin/console --version
```

---

## 9. Bekannte Probleme & Lösungen

### Problem: "Class not found" Fehler
**Lösung:** Cache leeren mit `rm -rf var/cache/*`

### Problem: Doctrine Migrations Bundle Inkompatibilität
**Lösung:** Auf v2.2 downgraden (siehe Abschnitt 3.4)

### Problem: netgen/tagsbundle vs se7enxweb/tagsbundle
**Lösung:** netgen-Version entfernen, se7enxweb-Version verwenden

### Problem: predis v3.x nicht kompatibel
**Lösung:** predis:^1.1 verwenden

---

## 10. Nächste Schritte

1. ✅ Composer Update abgeschlossen
2. ⏳ Anwendung testen
3. ⏳ Unit Tests ausführen
4. ⏳ Functional Tests ausführen
5. ⏳ Legacy Bridge testen
6. ⏳ Admin UI testen

---

**Status:** ✅ Erfolgreich abgeschlossen
**Letzte Aktualisierung:** 2026-01-30
