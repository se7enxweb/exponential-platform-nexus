# Exponential Platform Nexus 1.2.0.x is Platform v4 — 7x Netgen Media Site Variant

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-8892BF?logo=php&logoColor=white)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-5.4%20LTS-000000?logo=symfony&logoColor=white)](https://symfony.com)
[![Platform](https://img.shields.io/badge/Platform-v4%20OSS-orange)](https://github.com/se7enxweb)
[![License: GPL v2 (or any later version)](https://img.shields.io/badge/License-GPL%20v2%20(or%20any%20later%20version)-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)

> **Exponential Platform Nexus v4** is the **7x fork and customisation** of the
> Netgen Media Site blueprint for Ibexa DXP v4 (Open Source edition). It runs on
> **Symfony 5.4 LTS**, **PHP 8.2+** (tested on PHP 8.5), and includes the 7x
> `se7enxweb/site-bundle` 3.x fork that replaces `netgen/site-bundle` via Composer's
> `replace` directive, and the `ExponentialSqliteGateway` class for SQLite-compatible
> ID generation.
>
> This repository is the starting skeleton for all Nexus-branded Exponential Platform v4
> projects. Read this file and [doc/sevenx/INSTALL.md](doc/sevenx/INSTALL.md) before
> starting.

---

## Table of Contents

1. [Project Notice](#project-notice)
2. [Project Status](#project-status)
3. [Who is 7x](#who-is-7x)
4. [What is Exponential Platform Nexus v4?](#what-is-exponential-platform-nexus-v4)
5. [Technology Stack](#technology-stack)
6. [Requirements](#requirements)
7. [Quick Start](#quick-start)
8. [Main Features](#main-features)
9. [Installation](#installation)
10. [Key CLI Commands Reference](#key-cli-commands-reference)
11. [Database Conversion](#database-conversion)
12. [Access Points](#access-points)
13. [Issue Tracker](#issue-tracker)
14. [Where to Get More Help](#where-to-get-more-help)
15. [How to Contribute](#how-to-contribute)
16. [Copyright](#copyright)
17. [License](#license)
18. [Additional Documentation](#additional-documentation)

---

## Project Notice

> "This project is not associated with the original eZ Publish software or its original
> developer, eZ Systems. It is an independent, 7x-driven continuation and customisation."

This skeleton is developed and maintained by [7x (se7enx.com)](https://se7enx.com) and
is used as the canonical starting point for client projects built on Exponential Platform
v4 OSS with the Netgen Media Site technology stack.

---

## Project Status

**Active — branch `1.2.0.x`** is the current development line.

The Nexus v4 skeleton targets:
- **Symfony 5.4 LTS** kernel
- **PHP 8.2+** (PHP 8.5 is the tested and recommended runtime)
- **Ibexa DXP 4.6 Open Source Edition**
- **Netgen Layouts 1.4.x** with Ibexa Site API integration
- **Netgen Media Site** content model and demo data

Ongoing work focuses on:
- PHP 8.2 / 8.3 / 8.5 full compatibility across all dependencies
- SQLite zero-config development database (including composite PK workaround via `ExponentialSqliteGateway`)
- Dependency upgrades across Composer and Yarn ecosystems

---

## Who is 7x

[7x](https://se7enx.com) is the North American corporation driving the continued
development, support, hosting, and community growth of Exponential Platform and related
open source projects. 7x has supported Exponential Platform customers for over 24 years
(previously as Brookins Consulting).

**7x offers:**
- Commercial support subscriptions for Exponential Platform deployments
- Hosting on the Exponential Platform cloud infrastructure (`exponential.earth`)
- Custom development, migrations, upgrades, and training
- Community stewardship via [share.exponential.earth](https://share.exponential.earth)

---

## What is Exponential Platform Nexus v4?

Exponential Platform Nexus v4 is a **Symfony 5.4 LTS application skeleton** combining:

- **Ibexa DXP v4 Open Source Edition** — the Symfony-native content management
  platform providing the content repository, REST API v2, GraphQL, and the Platform v4
  Admin UI
- **Netgen Media Site** — a production-grade blueprint skeleton with demo content model,
  Netgen Layouts, Netgen Content Browser, Netgen Information Collection, and the full
  Netgen toolkit for Ibexa
- **7x Customisations** — the `se7enxweb/site-bundle` 3.x fork (replacing
  `netgen/site-bundle` via Composer `replace` directive) and the
  `ExponentialSqliteGateway` / `fixSqliteCompositePrimaryKeys()` SQLite workarounds for
  correct ID generation and composite primary key support

The skeleton is distributed via the `se7enxweb/exponential-platform-nexus` GitHub
repository and is composed using the `se7enxweb/oss` Composer metapackage.

---

## Technology Stack

| Component | Value |
|---|---|
| Language | PHP 8.2+ (PHP 8.5 recommended) |
| Framework | Symfony 5.4 LTS |
| CMS Core | Ibexa DXP v4 Open Source Edition |
| ORM / DBAL | Doctrine ORM + DBAL 3.x |
| Template Engine | Twig 3.x |
| Frontend Build | Webpack Encore + Yarn 1.x + **Node.js 18** |
| Page Builder | Netgen Layouts 1.4.x |
| Search | Legacy search (default) · Solr 8.x (optional) |
| HTTP Cache | Symfony HttpCache (default) · Varnish 6/7 (optional) |
| App Cache | Filesystem (default) · Redis 6+ (optional) |
| Database | SQLite 3.35+ (dev default) · MySQL 8.0+ · MariaDB 10.3+ · PostgreSQL 14+ |
| API | REST API v2 · GraphQL (schema auto-generated) · JWT auth |
| Admin UI | Ibexa Platform v4 Admin UI (`/adminui/`) |
| Dependency Mgmt | Composer 2.x · Yarn 1.x |
| Metapackage | `se7enxweb/oss ~4.6.0` |

---

## Requirements

- **PHP 8.2+** (PHP 8.5 recommended, tested on PHP 8.5.5)
- PHP extensions: `gd`, `curl`, `json`, `xsl`, `xml`, `intl`, `mbstring`, `ctype`, `iconv`,
  `pdo_sqlite` or `pdo_mysql` or `pdo_pgsql`
- **Composer 2.x**
- **Node.js 18** (via [nvm](https://github.com/nvm-sh/nvm) recommended)
- **Yarn 1.22.x** (via corepack after `nvm use 18`)
- A web server: Apache 2.4 or Nginx 1.18+ (or Symfony CLI for development)
- A database: SQLite 3.35+ (dev), MySQL 8.0+, MariaDB 10.3+, or PostgreSQL 14+

### Full Requirements Summary

| Component | Minimum | Recommended |
|---|---|---|
| PHP | 8.2 | 8.5 |
| Composer | 2.x | latest 2.x |
| Node.js | **18** | **18 LTS** (via nvm) |
| Yarn | 1.x | 1.22.22 (corepack) |
| MySQL | 8.0 | 8.0+ (utf8mb4) |
| MariaDB | 10.3 | 10.6+ |
| PostgreSQL | 14 | 16+ |
| SQLite | 3.35 | 3.39+ (dev/testing) |
| Redis | 6.0 | 7.x (optional) |
| Solr | 8.x | 8.11.x (optional) |
| Varnish | 6.0 | 7.1+ (optional) |
| Apache | 2.4 | 2.4 (event + PHP-FPM) |
| Nginx | 1.18 | 1.24+ |

---

## Quick Start

```bash
# 1. Clone the project
git clone git@github.com:se7enxweb/exponential-platform-nexus.git
cd exponential-platform-nexus
git checkout 1.2.0.x

# 2. Install PHP dependencies
COMPOSER_ALLOW_SUPERUSER=1 composer install

# 3. Configure environment (SQLite zero-config default — no server needed)
cp .env .env.local
# Edit .env.local: set APP_SECRET, and set DATABASE_URL for SQLite or MySQL/PostgreSQL

# 4. Install the demo database (creates var/data.db automatically for SQLite)
php bin/console exponential:install exponential-media --no-interaction

# 5. Fix permissions for the SQLite file (web server must be able to write it)
chmod 660 var/data.db
chown $USER:www-data var/data.db   # replace www-data with your FPM user

# 6. Activate Node.js 18
source ~/.nvm/nvm.sh && nvm use 18
corepack enable

# 7. Install Node dependencies
yarn install

# 8. Build site frontend assets (CSS/JS)
yarn build:prod

# 9. Publish Symfony bundle assets and build the Admin UI
php bin/console assets:install --symlink --relative public
yarn ibexa:build

# 10. Generate JWT keypair for REST API
php bin/console lexik:jwt:generate-keypair

# 11. Generate GraphQL schema
php bin/console ibexa:graphql:generate-schema

# 12. Clear the cache
php bin/console cache:clear

# 13. Start the Symfony CLI dev server
symfony server:start
```

After install, the following URLs are live:

| URL | Description |
|---|---|
| `https://127.0.0.1:8000/` | Public Symfony/Twig site (`site` siteaccess) |
| `https://127.0.0.1:8000/adminui/` | **Platform v4 Admin UI** (React) |
| `https://127.0.0.1:8000/api/ezp/v2/` | REST API v2 (JWT-authenticated) |
| `https://127.0.0.1:8000/graphql` | GraphQL endpoint |
| `https://127.0.0.1:8000/graphql/explorer` | GraphiQL browser (dev mode only) |
| `https://127.0.0.1:8000/nglayouts/admin` | Netgen Layouts admin |
| `https://127.0.0.1:8000/nglayouts/app` | Netgen Layouts app (iframe editor) |

Default credentials: **`admin` / `publish`** — change immediately after first login.

See [doc/sevenx/INSTALL.md](doc/sevenx/INSTALL.md) for the complete step-by-step
installation and operations guide.

---

## Main Features

- Full Ibexa DXP v4 OSS content repository (content classes, versions, translations, locations)
- Netgen Layouts 1.4.x page builder with Ibexa and Ibexa Site API integration
- Netgen Content Browser for content selection in layouts
- Netgen Information Collection (contact forms, data collection)
- Netgen Ibexa Site API (high-performance content read API, custom `twigGetAttribute`)
- Netgen Tags field type (taxonomy/tagging)
- RichText field type with XSL/AlloyEditor support
- REST API v2 (JWT-authenticated)
- GraphQL API (auto-generated from content model)
- Platform v4 Admin UI (React, at `/adminui/`)
- Netgen Layouts Admin UI (at `/nglayouts/admin`)
- Webpack Encore frontend build pipeline (Node.js 18, Yarn 1.x)
- SQLite zero-config database (default for development)
- MySQL 8.0+ / MariaDB 10.3+ / PostgreSQL 14+ for production
- Multi-siteaccess support
- Solr search engine support (optional)
- Varnish HTTP cache support (optional)
- Redis application cache support (optional)
- Sentry error tracking integration
- Kaliop Migration Bundle for content migrations
- Lexik JWT Authentication Bundle
- Google reCAPTCHA v3 integration
- `se7enxweb/mediata-ezpage-fieldtype-bundle` (Platform v4 eZPage field type)

---

## Installation

```bash
git clone git@github.com:se7enxweb/exponential-platform-nexus.git
cd exponential-platform-nexus
git checkout 1.2.0.x
```

See [doc/sevenx/INSTALL.md](doc/sevenx/INSTALL.md) for the complete installation and
operations guide covering:

- Requirements (PHP 8.2+, Node.js 18, etc.)
- Environment configuration (`.env.local` reference)
- Database setup (SQLite, MySQL/MariaDB, PostgreSQL)
- The `se7enxweb/site-bundle` 3.x fork — what it does and why
- SQLite composite PK workaround (`ExponentialSqliteGateway`)
- Web server setup (Apache 2.4, Nginx, Symfony CLI)
- File & directory permissions
- Frontend asset build (Webpack Encore / Yarn — **Node.js 18 required**)
- Admin UI asset build
- JWT keypair generation
- GraphQL schema generation
- Search index initialisation
- Cache management
- Day-to-day operations (start / stop / restart / deploy)
- Production deployment checklist
- Cron job setup
- Solr search engine integration (optional)
- Varnish HTTP cache integration (optional)
- Troubleshooting
- Database conversion (SQLite ↔ MySQL ↔ PostgreSQL)

---

## Key CLI Commands Reference

### Symfony Core

```bash
php bin/console list                                          # list all commands
php bin/console cache:clear                                   # clear dev cache
php bin/console cache:clear --env=prod                        # clear prod cache
php bin/console cache:warmup --env=prod                       # warm up prod cache
php bin/console assets:install --symlink --relative public    # publish bundle assets
php bin/console debug:router                                  # list all routes
php bin/console debug:container                               # list all services
php bin/console debug:config ibexa                            # dump Ibexa config
```

### Platform v4

```bash
php bin/console exponential:install exponential-media --no-interaction  # install demo DB
php bin/console exponential:reindex                                      # reindex search
php bin/console ibexa:cron:run                                           # run cron
php bin/console ibexa:graphql:generate-schema                            # regenerate GraphQL schema
php bin/console lexik:jwt:generate-keypair                               # generate JWT keys
php bin/console bazinga:js-translation:dump public/assets --merge-domains  # dump JS translations
php bin/console liip:imagine:cache:remove                                # clear image variation cache
php bin/console fos:httpcache:invalidate:path / --all                    # purge HTTP cache
```

### Doctrine / Migrations

```bash
php bin/console doctrine:migration:migrate --allow-no-migration   # run migrations
php bin/console doctrine:migration:status                         # migration status
php bin/console doctrine:schema:validate                          # validate schema
```

### Frontend / Asset Build (Node.js 18 required)

```bash
# Always activate Node.js 18 first
source ~/.nvm/nvm.sh && nvm use 18
corepack enable

# Site frontend
yarn build:prod     # build site CSS/JS for production (minified)
yarn build:dev      # build site CSS/JS with source maps
yarn watch          # watch mode — auto-rebuild site assets on change

# Admin UI
yarn ibexa:build    # build Platform v4 Admin UI — production
yarn ibexa:dev      # build Platform v4 Admin UI — dev mode
yarn ibexa:watch    # watch Admin UI assets

# Dependencies
yarn install        # install / sync all Node dependencies
```

---

## Database Conversion

See **[doc/sevenx/INSTALL.md — Section 22: Database Conversion](doc/sevenx/INSTALL.md#22-database-conversion)**
for the full command reference covering all conversion paths, tool install instructions,
`.env.local` DSN updates, and the post-conversion checklist.

Supported engines and conversion paths:

| From | To | Tool |
|---|---|---|
| MySQL / MariaDB | SQLite | `mysql2sqlite` (MIT) |
| PostgreSQL | SQLite | `pgloader` (BSD-like) |
| SQLite | MySQL / MariaDB | `sqlite3-to-mysql` (MIT) |
| SQLite | PostgreSQL | `pgloader` (BSD-like) |
| MySQL / MariaDB | PostgreSQL | `pgloader` (BSD-like) |
| PostgreSQL | MySQL / MariaDB | `pgloader` + CSV export |
| Oracle | PostgreSQL | `ora2pg` (GPL v3) — then use any path above |

---

## Access Points

After a fresh install with the Symfony CLI dev server (`symfony server:start`), all of
the following URLs are live. Replace `https://127.0.0.1:8000` with your actual domain
in production.

| URL | Description |
|---|---|
| `https://127.0.0.1:8000/` | Public Symfony/Twig site (`site` siteaccess) |
| `https://127.0.0.1:8000/adminui/` | **Platform v4 Admin UI** (React) — login: `admin` / `publish` |
| `https://127.0.0.1:8000/api/ezp/v2/` | REST API v2 (requires JWT) |
| `https://127.0.0.1:8000/graphql` | GraphQL endpoint |
| `https://127.0.0.1:8000/graphql/explorer` | GraphiQL browser (APP_ENV=dev only) |
| `https://127.0.0.1:8000/nglayouts/admin` | Netgen Layouts admin interface |
| `https://127.0.0.1:8000/nglayouts/app` | Netgen Layouts iframe editor |

**Default credentials: `admin` / `publish` — change immediately after first login.**

---

## Issue Tracker

Bugs, improvements and feature requests:
https://github.com/se7enxweb/exponential-platform-nexus/issues

Security issues: please report responsibly via email to
[security@exponential.one](mailto:security@exponential.one)

---

## Where to Get More Help

| Resource | URL |
|---|---|
| Platform Website | platform.exponential.earth |
| Documentation Hub | doc.exponential.earth |
| Community Forums | share.exponential.earth |
| GitHub Organisation | github.com/se7enxweb |
| This Repository | github.com/se7enxweb/exponential-platform-nexus |
| DXP Metapackage | github.com/se7enxweb/oss |
| Issue Tracker | [Issues](https://github.com/se7enxweb/exponential-platform-nexus/issues) |
| Telegram Chat | t.me/exponentialcms |
| 7x Corporate | se7enx.com |
| Support Subscriptions | support.exponential.earth |

---

## How to Contribute

1. Fork `se7enxweb/exponential-platform-nexus` on GitHub
2. Clone your fork and create a feature branch: `git checkout -b feature/my-improvement`
3. Install the dev stack per [doc/sevenx/INSTALL.md](doc/sevenx/INSTALL.md)
4. Make your changes and test thoroughly
5. Push your branch and open a Pull Request against `1.2.0.x`

When contributing fixes that touch the `se7enxweb/site-bundle` fork, changes must be
committed to the fork repository first, then the updated `composer.lock` committed here.

---

## Copyright

Copyright (C) 1998–2026 7x (formerly Brookins Consulting). All rights reserved.

Copyright (C) 1999–2025 Ibexa AS (formerly eZ Systems AS). All rights reserved.

Copyright (C) 2013–2026 Netgen. All rights reserved.

---

## License

This source code is available under the following licenses:

**A — Ibexa Business Use License Agreement (Ibexa BUL)**
version 2.4 or later. Granted by having a valid Ibexa DXP subscription.
See: https://www.ibexa.co/software-information/licenses-and-agreements

**AND**

**B — GNU General Public License, version 2**
Copyleft open source license with ABSOLUTELY NO WARRANTY.
See: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Copyright © 1998–2026 7x (se7enx.com). All rights reserved unless otherwise noted.

---

## Additional Documentation

* [7x Installation & Operations Guide](doc/sevenx/INSTALL.md) — **start here** for all
  installation, configuration, and operations details
* [Netgen Install Instructions](doc/netgen/INSTALL.md) — upstream Netgen Media Site install guide
* [Frontend Setup](doc/netgen/FRONTEND.md) — webpack/yarn frontend asset guide
* [Search Suggestions](doc/netgen/SEARCH_SUGGESTIONS.md) — search configuration
* [Ibexa Migrations](doc/netgen/IBEXA_MIGRATIONS.md) — Ibexa content migration guide
* [Apache vhost example](doc/apache2/media-site-vhost.conf)
* [Nginx vhost example](doc/nginx/media-site.conf)


