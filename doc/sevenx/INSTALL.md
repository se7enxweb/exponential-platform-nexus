# Exponential Platform Nexus v3 — 7x Installation & Operations Guide

> **Exponential Platform Nexus v3** is the 7x fork and customisation of the Netgen
> Media Site skeleton for eZ Platform 3.3 OSS. It runs on **Symfony 5.4 LTS** with
> **PHP 8.0+** (PHP 8.5 recommended) and includes the `se7enxweb/site-bundle` 2.x fork
> (replacing `netgen/site-bundle`) and the `ExponentialMediaInstaller` with a SQLite
> composite primary key workaround for correct content versioning on SQLite databases.
>
> This guide covers everything from first-time installation to day-to-day operations,
> production deployment, and troubleshooting.
>
> **Read this guide in full before starting.**

---

> **Console Command Prefix Convention**
>
> Commands in this distribution use the `exponential:` prefix where available.
> The `ibexa:*` prefix remains as a deprecated alias for migrated commands.
>
> | Preferred — use this | Deprecated (functional) |
> |---|---|
> | `exponential:*` | `ibexa:*` |
>
> Commands not yet migrated retain their `ibexa:*` name (e.g. `ibexa:cron:run`,
> `ibexa:graphql:generate-schema`). All `ibexa:*` commands have `ezplatform:*` aliases.

---

## Table of Contents

1. [Requirements](#1-requirements)
2. [Architecture Overview](#2-architecture-overview)
3. [7x Customisations — What They Fix and Why](#3-7x-customisations--what-they-fix-and-why)
   - [3a. `se7enxweb/site-bundle` 2.x fork](#3a-se7enxwebsite-bundle-2x-fork)
   - [3b. SQLite — v3-specific workarounds](#3b-sqlite--v3-specific-workarounds)
   - [3c. `se7enxweb/oss` metapackage](#3c-se7enxweboss-metapackage)
4. [First-Time Installation](#4-first-time-installation)
   - [4a. GitHub git clone (developers)](#4a-github-git-clone-developers)
5. [Environment Configuration (.env.local)](#5-environment-configuration-envlocal)
   - [Minimum required variables](#minimum-required-variables)
   - [MySQL / MariaDB](#mysql--mariadb)
   - [PostgreSQL](#postgresql-alternative-to-mysql)
   - [SQLite (zero-config — dev / testing)](#sqlite-zero-config--dev--testing)
   - [Search engine](#search-engine)
   - [HTTP cache](#http-cache)
   - [Application cache backend](#application-cache-backend)
   - [Mail](#mail)
   - [Other](#other)
6. [Database Setup](#6-database-setup)
   - [6a. MySQL / MariaDB](#6a-mysql--mariadb)
   - [6b. PostgreSQL](#6b-postgresql)
   - [6c. SQLite (zero-config)](#6c-sqlite-zero-config-database)
7. [Web Server Setup](#7-web-server-setup)
   - [7a. Apache 2.4](#7a-apache-24)
   - [7b. Nginx](#7b-nginx)
   - [7c. Symfony CLI (development only)](#7c-symfony-cli-development-only)
8. [File & Directory Permissions](#8-file--directory-permissions)
9. [Frontend Assets (Site CSS/JS)](#9-frontend-assets-site-cssjs)
10. [Admin UI Assets (eZ Platform Admin UI)](#10-admin-ui-assets-ez-platform-admin-ui)
11. [GraphQL Schema](#11-graphql-schema)
12. [Search Index](#12-search-index)
13. [Image Variations](#13-image-variations)
14. [Cache Management](#14-cache-management)
15. [Day-to-Day Operations: Start / Stop / Restart](#15-day-to-day-operations-start--stop--restart)
16. [Updating the Codebase](#16-updating-the-codebase)
17. [Cron Jobs](#17-cron-jobs)
18. [Solr Search Engine (optional)](#18-solr-search-engine-optional)
19. [Varnish HTTP Cache (optional)](#19-varnish-http-cache-optional)
20. [Troubleshooting](#20-troubleshooting)
21. [Database Conversion](#21-database-conversion)
22. [Complete CLI Reference](#22-complete-cli-reference)
23. [Git SSH Configuration (se7enxweb account)](#23-git-ssh-configuration-se7enxweb-account)

---

## 1. Requirements

### PHP

- **PHP 8.0+** (PHP 8.5 strongly recommended — the server at `alpha.se7enx.com` runs PHP 8.5.5)
- Required extensions: `gd` or `imagick`, `curl`, `json`, `pdo_mysql` or `pdo_pgsql` or `pdo_sqlite`,
  `xsl`, `xml`, `intl`, `mbstring`, `opcache`, `ctype`, `iconv`
- For SQLite: `pdo_sqlite` + `sqlite3` PHP extensions (usually bundled with PHP;
  verify with `php -m | grep -i sqlite`)
- `memory_limit` ≥ 256M (512M recommended)
- `date.timezone` must be set in `php.ini`
- `max_execution_time` ≥ 120 (300 recommended for CLI operations)

> **PHP 8.0 is the minimum** for this project. The `composer.json` declares `"php": "^8.0"`.

### Web Server

- **Apache 2.4** with `mod_rewrite`, `mod_deflate`, `mod_headers`, `mod_expires` enabled; run in
  `event` or `worker` mode with PHP-FPM _or_
- **Nginx 1.18+** with PHP-FPM

The server at `alpha.se7enx.com` runs PHP-FPM under pool `alpha:psacln`. All
`php bin/console` commands run as root but the FPM worker runs as `alpha:psacln`.
The SQLite database file (`var/data_dev.db` in dev, `var/data.db` in prod) must be owned
`alpha:psacln` with mode `660` for FPM to write it.

### Node.js & Yarn

- [Node.js](https://nodejs.org/en/download/) **18 or 20** — managed via
  [nvm](https://github.com/nvm-sh/nvm) (strongly recommended)
- [Yarn](https://classic.yarnpkg.com/en/docs/install) **1.22.x** — activated via
  [corepack](https://github.com/nodejs/corepack) `enable` after `nvm use 18` or `nvm use 20`

Installing nvm + Node.js 18:

```bash
# Universal installer — Linux, macOS, BSD, WSL
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.2/install.sh | bash
source ~/.nvm/nvm.sh      # or restart your shell
nvm install 18
nvm use 18
corepack enable            # activates Yarn 1.22.x
```

### Composer

- [Composer](https://getcomposer.org/) **2.x** — run `composer self-update` to ensure latest 2.x

```bash
# Universal installer (all UNIX / macOS / BSD)
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --2
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

### Database

- [MySQL](https://dev.mysql.com/downloads/mysql/) **8.0+** with `utf8mb4` character set and
  `utf8mb4_unicode_520_ci` collation _or_
- [MariaDB](https://mariadb.org/download/) **10.3+** (10.6+ recommended) _or_
- [PostgreSQL](https://www.postgresql.org/download/) **14+** _or_
- [SQLite](https://www.sqlite.org/download.html) **3.35+** — no server required; the `.db` file
  is created automatically on first install. **Default for development in this project.**

### Full Requirements Summary

| Component | Minimum | Recommended |
|---|---|---|
| PHP | 8.0 | 8.5 |
| Composer | 2.x | latest 2.x |
| Node.js | **18** | **18 LTS or 20 LTS** (via nvm) |
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

## 2. Architecture Overview

Exponential Platform Nexus v3 is a **single-kernel Symfony 5.4 LTS application** with:

```
Browser Request
      │
      ▼
   Web Server (Apache / Nginx)
      │
      ▼
  public/index.php  ── Symfony Kernel (eZ Platform 3.3 OSS — Symfony 5.4 LTS)
      │
      ├── URI: /adminui/**           → eZ Platform v3 Admin UI (React)        ← siteaccess: adminui
      ├── URI: /ngadminui/**         → Netgen Admin UI                        ← siteaccess: ngadminui
      ├── URI: /legacy_admin/**      → Legacy eZ Publish Admin                ← siteaccess: legacy_admin
      ├── URI: /api/ezp/v2/**        → REST API v2                            ← siteaccess: adminui
      ├── URI: /graphql              → GraphQL API                            ← siteaccess: adminui
      ├── URI: /nglayouts/**         → Netgen Layouts admin + app             ← siteaccess: adminui
      └── URI: /**                   → eZ Platform Twig/Symfony Front End     ← siteaccess: fh_eng / bold_eng / etc.
                                           Symfony controllers + Twig templates
```

Siteaccess matching uses `URIElement: 1` — the first URI path segment selects the
siteaccess. The default siteaccess (`fh_eng`) is served at the root `/`.

### Key Directories

```
project-root/
├── assets/                         Webpack Encore source (JS, SCSS, Docbook)
│   ├── js/                         JavaScript entry points and components
│   ├── sass/                       SCSS stylesheets
│   ├── docbook/                    Docbook XML/XSL for RichText
│   └── symlink/                    Symlink targets for public assets
├── config/
│   ├── bundles.php                 Bundle registration
│   ├── packages/                   Symfony/eZ/Netgen package config
│   ├── routes/                     Route inclusions (including netgen_layouts.yaml)
│   └── app/services.yaml           Application service definitions
├── src/
│   ├── Controller/                 Symfony controllers
│   ├── Entity/                     Doctrine entities
│   ├── Installer/                  exponential:install command + ExponentialMediaInstaller
│   └── Kernel.php
├── templates/
│   └── themes/                     Twig templates by siteaccess/theme
├── public/                         Web root
│   ├── assets/                     Built site frontend assets
│   └── bundles/                    Symfony bundle public assets (symlinked)
├── var/
│   ├── cache/                      Symfony application cache
│   ├── log/                        Application logs
│   ├── sessions/                   PHP session files
│   ├── data_dev.db                 SQLite database — development (APP_ENV=dev)
│   └── data.db                     SQLite database — production (APP_ENV=prod)
├── data/
│   ├── sqlite/media_schema.sql     Extra schema tables (nglayouts, etc.) for SQLite
│   ├── sqlite/media_data.sql       Demo seed data (SQLite-compatible INSERT statements)
│   ├── mysql/media_data.sql        Demo seed data (MySQL)
│   └── postgresql/media_data.sql   Demo seed data (PostgreSQL)
├── composer.json                   Declares se7enxweb/oss ~3.3.0
├── package.json                    Declares Node.js 18 or 20 engine requirement
└── webpack.config.js               Webpack Encore config (ez + project builds)
```

### Siteaccesses

| Siteaccess | URL prefix | Purpose |
|---|---|---|
| `fh_eng` | `/fh_eng/` or `/` (default) | Public front end (FH design, English) |
| `bold_eng` | `/bold_eng/` | Public front end (Bold design, English) |
| `bold_ger` | `/bold_ger/` | Public front end (Bold design, German) |
| `adminui` | `/adminui/` | eZ Platform v3 Admin UI + REST API + GraphQL + Netgen Layouts |
| `ngadminui` | `/ngadminui/` | Netgen Admin UI |
| `legacy_admin` | `/legacy_admin/` | Legacy eZ Publish Admin |

---

## 3. 7x Customisations — What They Fix and Why

This section is the canonical technical reference for every customisation applied by 7x
to this v3 project. Read this before debugging any error.

### 3a. `se7enxweb/site-bundle` 2.x fork

- **Replaces**: `netgen/site-bundle` (via Composer `replace` directive)
- **GitHub**: https://github.com/se7enxweb/site-bundle
- **Packagist**: https://packagist.org/packages/se7enxweb/site-bundle
- **Active version**: `~2.1.5.1`
- **Installed via**: directly in `composer.json` as `"se7enxweb/site-bundle": "~2.1.5.1"`

#### What the `replace` directive does

The fork's `composer.json` declares:
```json
"name": "se7enxweb/site-bundle",
"replace": {
    "netgen/site-bundle": "*"
}
```

This tells Composer that `se7enxweb/site-bundle` is a drop-in replacement for
`netgen/site-bundle`. Any package in the dependency tree that declares
`"require": {"netgen/site-bundle": "..."}` will have that requirement satisfied by
the fork without modifying any other package's `composer.json`.

This is important because `netgen/media-site-data` and other Netgen packages require
`netgen/site-bundle`. The `replace` directive satisfies those requirements automatically.

#### Why the fork exists

The `se7enxweb/site-bundle` 2.x fork provides the v3-compatible version of the Netgen
site bundle maintained by 7x. It ensures compatibility with `se7enxweb/oss ~3.3.0`
(the eZ Platform 3.3 metapackage) and receives security and compatibility updates from 7x.

---

### 3b. SQLite — v3-specific workarounds

> **This section is critical for SQLite users.** Skip if using MySQL/MariaDB or PostgreSQL
> — this workaround is a no-op on those databases.

One SQLite-specific problem exists in eZ Platform 3.3 that is fixed in this project:

#### Composite PK collapsed to single PK by SQLite Doctrine schema

eZ Platform's schema defines three tables with `PRIMARY KEY (id, version)` (composite):
- `ezcontentobject_attribute`
- `ezcontentclass`
- `ezcontentclass_attribute`

When Doctrine creates these tables on SQLite via `CREATE TABLE`, it collapses the
`INTEGER` column in a composite PK to a plain `INTEGER` (not `INTEGER PRIMARY KEY`).
SQLite then enforces `id` alone as unique, which breaks eZ Platform's versioning model:
when a new draft is created, `insertExistingField()` reuses the same `id` with a new
`version` — valid on MySQL/PostgreSQL (composite PK), but fails on SQLite with:

```
UNIQUE constraint failed: ezcontentobject_attribute.id
```

**Fix — `fixSqliteCompositePrimaryKeys()` in `ExponentialMediaInstaller`:**

Located in `src/Installer/ExponentialMediaInstaller.php`.
Called automatically during `exponential:install exponential-media` after the base
schema is imported. The method:

1. Is a no-op on MySQL and PostgreSQL — only runs on SQLite
2. For each of the three affected tables, recreates it with the correct
   `PRIMARY KEY (id, version)` composite definition using a rename-swap:
   - `CREATE TABLE {table}_cpkfix (... PRIMARY KEY (id, version))`
   - `INSERT INTO {table}_cpkfix SELECT * FROM {table}`
   - `DROP TABLE {table}`
   - `ALTER TABLE {table}_cpkfix RENAME TO {table}`

This fix is applied BEFORE seed data is imported, so all inserted rows and all
subsequent content edits work correctly.

---

### 3c. `se7enxweb/oss` metapackage

- **GitHub**: https://github.com/se7enxweb/oss
- **Packagist**: https://packagist.org/packages/se7enxweb/oss
- **Branch**: version `~3.3.0`

This is the **central metapackage** that wires the entire Exponential Platform v3
dependency graph together. It:

- Requires all eZ Platform 3.3 OSS packages (via `se7enxweb/ezplatform-kernel ~1.3`)
- Requires all Symfony 5.4 LTS packages
- Requires all supporting Doctrine, Twig, and Symfony ecosystem packages

**You should not require eZ Platform core packages directly in this project's `composer.json`.**
The metapackage handles all of that. To update the core:

```bash
COMPOSER_ALLOW_SUPERUSER=1 composer update se7enxweb/oss
php bin/console cache:clear
```

---

## 4. First-Time Installation

### 4a. GitHub git clone (developers)

```bash
git clone git@github.com:se7enxweb/exponential-platform-nexus.git
cd exponential-platform-nexus
git checkout 1.1.0.x
```

#### Step 1 — Install PHP dependencies

```bash
COMPOSER_ALLOW_SUPERUSER=1 composer install
```

This installs all packages, runs Symfony Flex recipes, and runs the `post-install-cmd`
scripts (`cache:clear`, `assets:install`, `ngsite:symlink:project`).

> 💾 **Git Save Point 1 — Vendors installed**
> ```bash
> git add composer.lock && git commit -m "chore(install): lock vendor dependencies"
> ```

#### Step 2 — Configure environment

See [Section 5](#5-environment-configuration-envlocal).

#### Step 3 — Create the database and import demo data

See [Section 6](#6-database-setup).

#### Step 4 — Set permissions

See [Section 8](#8-file--directory-permissions).

#### Step 5 — Build frontend assets (Node.js 18 or 20 required)

```bash
source ~/.nvm/nvm.sh && nvm use 18    # REQUIRED — do not skip this
corepack enable
yarn install
yarn build:prod
```

#### Step 6 — Build Admin UI assets

```bash
php bin/console assets:install --symlink --relative public
yarn ez   # builds eZ/Ibexa admin UI assets
```

#### Step 7 — Generate GraphQL schema

```bash
php bin/console ibexa:graphql:generate-schema
```

#### Step 8 — Clear all caches

```bash
php bin/console cache:clear
```

#### Step 9 — Reindex search

```bash
php bin/console exponential:reindex
```

> 💾 **Git Save Point 2 — Installation complete**
> ```bash
> git add -A && git commit -m "chore(install): exponential-platform-nexus install complete"
> ```

#### Step 10 — Start the dev server

```bash
symfony server:start
```

All access points after install:

| URL | Description |
|---|---|
| `https://127.0.0.1:8000/` | eZ Platform Twig public site (default siteaccess) |
| `https://127.0.0.1:8000/adminui/` | **eZ Platform v3 Admin UI** (React) |
| `https://127.0.0.1:8000/ngadminui/` | Netgen Admin UI |
| `https://127.0.0.1:8000/legacy_admin/` | Legacy eZ Publish Admin |
| `https://127.0.0.1:8000/api/ezp/v2/` | REST API v2 |
| `https://127.0.0.1:8000/graphql` | GraphQL endpoint |
| `https://127.0.0.1:8000/adminui/nglayouts/admin` | Netgen Layouts admin |
| `https://127.0.0.1:8000/adminui/nglayouts/app` | Netgen Layouts app editor |

Default credentials: `admin` / `publish` — **change immediately after first login**.

---

## 5. Environment Configuration (.env.local)

Never commit `.env.local`. It overrides `.env` with host-specific secrets.

```bash
cp .env .env.local
$EDITOR .env.local
```

### Minimum required variables

```bash
# Application
APP_ENV=prod             # or dev
APP_SECRET=<random-32-char-hex-string>   # generate: openssl rand -hex 16
```

### MySQL / MariaDB

```bash
DATABASE_DRIVER=pdo_mysql
DATABASE_HOST=127.0.0.1
DATABASE_PORT=3306
DATABASE_NAME=your_db_name
DATABASE_USER=your_db_user
DATABASE_PASSWORD=your_db_password
DATABASE_CHARSET=utf8mb4
DATABASE_COLLATION=utf8mb4_unicode_520_ci
DATABASE_VERSION=mariadb-10.6.0    # e.g. mariadb-10.6.0 or 8.0 for MySQL

# Or use a full DSN (takes precedence over the vars above):
# DATABASE_URL="mysql://user:pass@127.0.0.1:3306/dbname?serverVersion=8.0&charset=utf8mb4"
```

### PostgreSQL (alternative to MySQL)

```bash
DATABASE_DRIVER=pdo_pgsql
DATABASE_HOST=127.0.0.1
DATABASE_PORT=5432
DATABASE_NAME=your_db_name
DATABASE_USER=your_db_user
DATABASE_PASSWORD=your_db_password
DATABASE_CHARSET=utf8
DATABASE_VERSION=16
```

### SQLite (zero-config — dev / testing)

SQLite is the **zero-config development option** for this project. The `.db` file is
created automatically when you run `exponential:install`. No database server is required.

In v3, the database filename includes the environment name:

```bash
# In .env.local — the default (environment-specific filename):
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
# Results in: var/data_dev.db (APP_ENV=dev) or var/data.db (APP_ENV=prod)

# Or use a fixed filename:
# DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"

# Required for SQLite — processes messages synchronously:
MESSENGER_TRANSPORT_DSN=sync://
```

**SQLite file permissions (critical):**

The install command typically runs as your shell user (or root). The web server /
PHP-FPM pool must have write access to the `.db` file:

```bash
# On alpha.se7enx.com (PHP-FPM pool: alpha:psacln):
chmod 660 var/data_dev.db    # or var/data.db in prod
chown alpha:psacln var/data_dev.db

# Generic (adjust www-data to your FPM user):
chmod 660 var/data_dev.db
chown $USER:www-data var/data_dev.db
```

**Verify PHP extensions:**
```bash
php -m | grep -i sqlite
# Must show:
#   SQLite3
#   pdo_sqlite
```

### Search engine

```bash
SEARCH_ENGINE=legacy       # default — uses the eZ Platform legacy search engine
# SEARCH_ENGINE=solr       # use Solr (see Section 18)
```

### HTTP cache

```bash
HTTPCACHE_PURGE_TYPE=local          # or "varnish" when using Varnish
HTTPCACHE_DEFAULT_TTL=86400
HTTPCACHE_PURGE_SERVER=http://localhost:80
# HTTPCACHE_VARNISH_INVALIDATE_TOKEN=<your-secret>
# TRUSTED_PROXIES=127.0.0.1
```

### Application cache backend

```bash
CACHE_POOL=cache.tagaware.filesystem    # default (filesystem)
# CACHE_POOL=cache.redis                # use Redis
# CACHE_DSN=redis://localhost:6379
```

### Mail

```bash
MAILER_DSN=null://null      # dev (suppress delivery)
# MAILER_DSN=smtp://localhost:25
```

### Other

```bash
IMAGEMAGICK_PATH=/usr/bin
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
SESSION_HANDLER_ID=session.handler.native_file
SESSION_SAVE_PATH=%kernel.project_dir%/var/sessions/%kernel.environment%
SENTRY_DSN=                 # leave empty to disable Sentry error reporting
```

---

## 6. Database Setup

### 6a. MySQL / MariaDB

```sql
CREATE DATABASE your_db_name
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_520_ci;

GRANT ALL PRIVILEGES ON your_db_name.* TO 'your_db_user'@'localhost'
  IDENTIFIED BY 'your_db_password';
FLUSH PRIVILEGES;
```

Then run the installer:

```bash
php bin/console exponential:install exponential-media --no-interaction
```

### 6b. PostgreSQL

```bash
psql -U postgres -c "CREATE DATABASE your_db_name ENCODING 'UTF8';"
psql -U postgres -c "CREATE USER your_db_user WITH PASSWORD 'your_db_password';"
psql -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE your_db_name TO your_db_user;"
```

Then run the installer:

```bash
php bin/console exponential:install exponential-media --no-interaction
```

### 6c. SQLite (zero-config database)

SQLite requires no prior setup. The installer creates the file automatically, imports
all schema and seed data, and applies the composite PK fix (see [Section 3b](#3b-sqlite--v3-specific-workarounds)).

#### Step 1 — Verify PHP extensions

```bash
php -m | grep -i sqlite
# Expected:
#   SQLite3
#   pdo_sqlite
```

#### Step 2 — Configure `.env.local`

```bash
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
MESSENGER_TRANSPORT_DSN=sync://
```

Remove or comment out any `DATABASE_DRIVER`, `DATABASE_HOST`, `DATABASE_PORT`,
`DATABASE_NAME`, `DATABASE_USER`, `DATABASE_PASSWORD` lines.

#### Step 3 — Run the install command

```bash
php bin/console exponential:install exponential-media --no-interaction
```

This single command:
1. Creates `var/data_dev.db` (the SQLite database file for `APP_ENV=dev`)
2. Imports the eZ Platform platform schema
3. Applies `fixSqliteCompositePrimaryKeys()` to set correct `PRIMARY KEY (id, version)` on three tables
4. Imports extra schema (`data/sqlite/media_schema.sql`)
5. Imports seed content data (`data/sqlite/media_data.sql`)
6. Creates all Doctrine ORM tables

Default credentials after install: **`admin` / `publish`**

The installer is idempotent — `data/sqlite/media_schema.sql` uses `DROP TABLE IF EXISTS`
before each `CREATE TABLE`, so you can run it multiple times safely.

#### Step 4 — Fix file permissions

The web server / FPM must be able to write the `.db` file:

```bash
# alpha.se7enx.com specific:
chmod 660 var/data_dev.db
chown alpha:psacln var/data_dev.db

# Generic:
chmod 660 var/data_dev.db
chown $USER:www-data var/data_dev.db   # replace with your FPM user
```

#### Step 5 — Clear caches

```bash
php bin/console cache:clear
```

> 💾 **Git Save Point — SQLite install complete**
> ```bash
> git commit --allow-empty -m "chore(install): sqlite database provisioned for dev"
> ```

---

## 7. Web Server Setup

### 7a. Apache 2.4

Enable required modules:

```bash
a2enmod rewrite deflate headers expires
```

Example virtual host (see also `doc/apache2/media-site-vhost.conf`):

```apache
<VirtualHost *:443>
    ServerName your-site.example.com
    DocumentRoot /var/www/vhosts/your-site/public

    SSLEngine on
    SSLCertificateFile    /etc/ssl/certs/your-site.crt
    SSLCertificateKeyFile /etc/ssl/private/your-site.key

    SetEnvIf Request_URI ".*" APP_ENV=prod
    SetEnv APP_DEBUG "0"
    SetEnv APP_HTTP_CACHE "1"

    <Directory /var/www/vhosts/your-site/public>
        AllowOverride None
        Require all granted

        FallbackResource /index.php

        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} -f [OR]
        RewriteCond %{REQUEST_FILENAME} -d
        RewriteRule ^ - [L]
        RewriteRule ^ /index.php [L]
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/your-site_error.log
    CustomLog ${APACHE_LOG_DIR}/your-site_access.log combined
</VirtualHost>
```

For HTTP → HTTPS redirect:

```apache
<VirtualHost *:80>
    ServerName your-site.example.com
    Redirect permanent / https://your-site.example.com/
</VirtualHost>
```

### 7b. Nginx

See also `doc/nginx/media-site.conf` and `doc/nginx/ibexa_params.d/`.

```nginx
server {
    listen 443 ssl http2;
    server_name your-site.example.com;
    root /var/www/vhosts/your-site/public;
    index index.php;

    ssl_certificate    /etc/ssl/certs/your-site.crt;
    ssl_certificate_key /etc/ssl/private/your-site.key;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;   # adjust PHP version as needed
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT   $realpath_root;
        fastcgi_param APP_ENV         prod;
        fastcgi_param APP_DEBUG       0;
        fastcgi_param APP_HTTP_CACHE  1;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    access_log /var/log/nginx/your-site_access.log;
    error_log  /var/log/nginx/your-site_error.log;
}

server {
    listen 80;
    server_name your-site.example.com;
    return 301 https://$host$request_uri;
}
```

```bash
nginx -t && systemctl reload nginx
```

### 7c. Symfony CLI (development only)

```bash
# Universal installer
curl -sS https://get.symfony.com/cli/installer | bash
mv ~/.symfony5/bin/symfony /usr/local/bin/symfony

symfony server:start                # HTTPS dev server on https://127.0.0.1:8000
symfony server:start -d             # run in background
symfony server:stop                 # stop background server
symfony server:log                  # tail server log
symfony server:status               # show status + URL
```

---

## 8. File & Directory Permissions

Replace `www-data` with your actual web server / PHP-FPM user.

On `alpha.se7enx.com`, the FPM pool runs as `alpha:psacln`.

```bash
# Symfony runtime directories
setfacl -R  -m u:www-data:rwX -m g:www-data:rwX var/
setfacl -dR -m u:www-data:rwX -m g:www-data:rwX var/

# eZ Platform public var directory (thumbnails, generated content)
setfacl -R  -m u:www-data:rwX -m g:www-data:rwX public/var/
setfacl -dR -m u:www-data:rwX -m g:www-data:rwX public/var/
```

For SQLite, also fix the database file (see [Section 6c Step 4](#step-4--fix-file-permissions)).

If `setfacl` is unavailable:

```bash
chown -R www-data:www-data var/ public/var/
chmod -R 775 var/ public/var/
```

---

## 9. Frontend Assets (Site CSS/JS)

The project uses Webpack Encore + Yarn. **Node.js 18 or 20 is required.**

```bash
source ~/.nvm/nvm.sh && nvm use 18    # REQUIRED
corepack enable                        # activates Yarn 1.22.x
```

### Install Node dependencies (first time or after `package.json` changes)

```bash
yarn install
```

### Build for development (with source maps)

```bash
yarn build:dev
```

### Build for production (minified)

```bash
yarn build:prod
```

### Watch mode (auto-rebuild on file change)

```bash
yarn watch
```

### What to rebuild after changes

| Changed files | Command |
|---|---|
| `assets/js/**`, `assets/sass/**` | `yarn build:dev` (or `yarn watch`) |
| `package.json` | `yarn install && yarn build:dev` |
| `webpack.config.project.js` | `yarn build:dev` |

---

## 10. Admin UI Assets (eZ Platform Admin UI)

The eZ Platform Admin UI assets (React components, SCSS, icons) are built separately from
the site frontend using `webpack.config.ez.js`. They are not rebuilt automatically on
`composer install`.

### Prerequisites

The `var/encore/` directory must be populated by `assets:install` before any admin UI
build can run:

```bash
php bin/console assets:install --symlink --relative public
```

This publishes bundle `public/` directories to `public/bundles/` and writes the Encore
configuration files needed by `webpack.config.ez.js`.

### Build Admin UI assets — production

```bash
nvm use 18 && yarn ez   # builds eZ/Ibexa admin UI assets
```

### What changes require an Admin UI asset rebuild

| Change | Rebuild needed |
|---|---|
| `composer update` pulled a new bundle version | Yes — `yarn ez` |
| Any bundle's `Resources/public/` JS or SCSS changed | Yes — `yarn ez` |
| `webpack.config.ez.js` modified | Yes — `yarn ez` |
| Admin RichText editor configuration changed | Yes — `yarn ez` |

---

## 11. GraphQL Schema

The GraphQL schema is auto-generated from the content type model. Regenerate it after
any content type or field type changes:

```bash
php bin/console ibexa:graphql:generate-schema
# alias: php bin/console ezplatform:graphql:generate-schema
php bin/console cache:clear
```

The GraphQL endpoint is at `/graphql`. The GraphiQL browser UI is at
`/graphql` when `APP_ENV=dev`.

---

## 12. Search Index

### Full reindex (required after install or bulk content import)

```bash
php bin/console exponential:reindex
```

### Incremental reindex

```bash
php bin/console exponential:reindex --iteration-count=100
```

### Reindex a specific content type

```bash
php bin/console exponential:reindex --content-type=article
```

---

## 13. Image Variations

Image variations are generated on demand by Liip Imagine. Configuration lives in
`config/packages/ezpublish.yaml` under `ezpublish.system.<siteaccess>.image_variations`.

### Clear generated variation cache

```bash
php bin/console liip:imagine:cache:remove
php bin/console cache:clear
```

---

## 14. Cache Management

### Clear Symfony application cache

```bash
php bin/console cache:clear                    # current APP_ENV
php bin/console cache:clear --env=prod         # production cache
```

### Warm up cache (production)

```bash
php bin/console cache:warmup --env=prod
```

### Nuclear option (development)

```bash
rm -rf var/cache/dev var/cache/prod
php bin/console cache:warmup --env=prod
```

> When running `composer update` as root and the Symfony app runs as a different FPM
> user (e.g. `alpha:psacln`), the cache warmup writes files as root. The FPM process
> cannot read them, and the site serves stale content or 500 errors. The fix is to
> ensure cache files are world-readable, or to run warmup as the FPM user:
>
> ```bash
> rm -rf var/cache/dev/* var/cache/prod/*
> php bin/console cache:warmup --env=prod
> chmod -R a+rX var/cache/
> ```

---

## 15. Day-to-Day Operations: Start / Stop / Restart

### Apache

```bash
systemctl start apache2
systemctl stop apache2
systemctl restart apache2
systemctl reload apache2    # graceful reload
```

### Nginx

```bash
systemctl start nginx
systemctl stop nginx
systemctl reload nginx
nginx -s reload             # alternative graceful reload
```

### PHP-FPM

On servers managed by Plesk, the FPM service is named per-version:

```bash
# Plesk-managed PHP-FPM (adjust version number as appropriate):
systemctl restart plesk-php83-fpm
systemctl restart plesk-php84-fpm
systemctl restart plesk-php85-fpm

# Standard (non-Plesk) PHP-FPM:
systemctl restart php8.5-fpm
systemctl reload php8.5-fpm    # graceful reload after config changes
```

### Symfony CLI dev server

```bash
symfony server:start -d      # start in background
symfony server:stop          # stop
symfony server:log           # view logs
symfony server:status        # show status + URL
```

### After deploying code changes (production checklist)

```bash
# 1. Pull code
git pull --rebase

# 2. Install/update vendors
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev -o

# 3. Run Doctrine migrations
php bin/console doctrine:migration:migrate --allow-no-migration --env=prod

# 4. Publish bundle public assets
php bin/console assets:install --symlink --relative public --env=prod

# 5. Rebuild eZ Platform Admin UI assets (if admin-ui bundle updated)
source ~/.nvm/nvm.sh && nvm use 18 && yarn ez   # builds eZ/Ibexa admin UI assets

# 6. Rebuild frontend site assets (if theme/JS/CSS changed)
yarn build:prod

# 7. Clear & warm up caches
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Fix cache permissions if running as root but FPM is a different user:
chmod -R a+rX var/cache/

# 8. Reindex search (if content model changed)
# php bin/console exponential:reindex --env=prod
```

> 💾 **Git Save Point — After each production deploy**
> ```bash
> git tag -a "deploy-$(date +%Y%m%d-%H%M)" -m "production deploy $(date)"
> git push origin --tags
> ```

---

## 16. Updating the Codebase

### Pull latest code and rebuild

```bash
git pull --rebase
COMPOSER_ALLOW_SUPERUSER=1 composer install
php bin/console doctrine:migration:migrate --allow-no-migration
php bin/console cache:clear
```

### Update Composer packages

```bash
# Update all packages within constraints
COMPOSER_ALLOW_SUPERUSER=1 composer update

# Update a single package (e.g. after a fork fix is pushed to Packagist):
COMPOSER_ALLOW_SUPERUSER=1 composer update se7enxweb/site-bundle

# After update, run:
php bin/console doctrine:migration:migrate --allow-no-migration
php bin/console cache:clear
```

> 💾 **Git Save Point — After composer update**
> ```bash
> git add composer.lock && git commit -m "chore(deps): composer update $(date +%Y-%m-%d)"
> ```

---

## 17. Cron Jobs

Add to crontab (`crontab -e -u www-data` or for the FPM user):

```bash
# eZ Platform / Ibexa cron runner (every 5 minutes)
*/5 * * * * /usr/bin/php /path/to/project/bin/console ibexa:cron:run --env=prod >> /var/log/nexus-cron.log 2>&1
# alias: ezplatform:cron:run
```

---

## 18. Solr Search Engine (optional)

### Switch from legacy to Solr

1. Set `SEARCH_ENGINE=solr` and `SOLR_DSN`/`SOLR_CORE` in `.env.local`
2. Clear cache: `php bin/console cache:clear`
3. Provision the Solr core:
   ```bash
   curl "http://localhost:8983/solr/admin/cores?action=CREATE&name=collection1&configSet=exponential"
   ```
4. Reindex all content:
   ```bash
   php bin/console exponential:reindex
   ```

### Switch back to legacy search

```bash
SEARCH_ENGINE=legacy
php bin/console cache:clear
```

---

## 19. Varnish HTTP Cache (optional)

### Enable Varnish caching

1. Set in `.env.local`:
   ```bash
   HTTPCACHE_PURGE_TYPE=varnish
   HTTPCACHE_PURGE_SERVER=http://localhost:6081
   HTTPCACHE_VARNISH_INVALIDATE_TOKEN=<your-secret>
   TRUSTED_PROXIES=127.0.0.1
   ```
2. Deploy the included VCL (`doc/varnish/` if available) or the upstream eZ Platform VCL
3. Reload Varnish: `systemctl reload varnish`
4. Clear the cache: `php bin/console fos:httpcache:invalidate:path / --all`

---

## 20. Troubleshooting

### Blank page / 500 error after install

```bash
tail -50 var/log/dev.log      # or prod.log
tail -50 /var/log/apache2/error.log    # or nginx error log
php bin/console cache:clear
```

### "Class not found" after `composer install`

```bash
composer dump-autoload
php bin/console cache:clear
```

### SQLite "attempt to write a readonly database"

The FPM pool user cannot write the `.db` file:

```bash
# Check ownership:
ls -la var/data_dev.db

# Fix:
chmod 660 var/data_dev.db
chown alpha:psacln var/data_dev.db   # replace with your FPM user
```

### SQLite "UNIQUE constraint failed: ezcontentobject_attribute.id"

The composite PK fix was not applied. Re-run the installer:

```bash
rm var/data_dev.db
php bin/console exponential:install exponential-media --no-interaction
chmod 660 var/data_dev.db && chown alpha:psacln var/data_dev.db
```

### Stale cache after deploy

```bash
rm -rf var/cache/dev/* var/cache/prod/*
php bin/console cache:warmup --env=prod
chmod -R a+rX var/cache/
```

### Node.js / Yarn build errors

```bash
# Ensure correct Node version:
source ~/.nvm/nvm.sh && nvm use 18
node -v    # must show 18.x.x

# Clear and reinstall:
rm -rf node_modules
yarn install
yarn ez
```

### Admin UI assets missing (blank admin pages, no styles)

```bash
php bin/console assets:install --symlink --relative public
yarn ez
```

### Netgen Layouts "Invalid Target" or "No layout applied" on frontend

Check that nglayouts rule targets and conditions use v3 identifiers:
- Rule targets: `ez_location`, `ez_subtree` (with underscore)
- Rule conditions: `ez_content_type` (with underscore)
- Query types: `ezcontent_search` (no underscore)
- Block definitions: `ezcomponent` (no underscore)
- Collection item value_type: `ezlocation` (no underscore — matches the registered value loader)
- Link URI scheme in block parameters: `ezlocation://` (not `ibexa-location://`)

If the data was migrated from a v4 installation, check your `data/sqlite/media_data.sql`
and run the `UPDATE` statements to fix any v4 identifiers in the live database.

---

## 21. Database Conversion

This section covers converting an existing, running Exponential Platform Nexus v3
application from one database engine to another using **free and open-source tools only**.

All tools listed below are either:
- Distributed under OSI-approved open-source licences (MIT, GPL, BSD, Apache 2.0), or
- Free CLI utilities included with the database server packages.

> **Before you start — backup everything.**
> ```bash
> # MySQL / MariaDB:
> mysqldump -u "$DATABASE_USER" -p"$DATABASE_PASSWORD" -h "$DATABASE_HOST" "$DATABASE_NAME" > backup_$(date +%Y%m%d).sql
> # PostgreSQL:
> pg_dump -U pg_user exponential > backup_$(date +%Y%m%d).sql
> # SQLite:
> cp var/data_dev.db var/data_dev.db.bak    # or var/data.db for prod
> # Also backup .env.local
> cp .env.local .env.local.bak
> ```

### Tool inventory

All tools are free and open-source.

#### `mysqldump` / `mysql` CLI

Bundled with every MySQL and MariaDB server package.

| OS | Install |
|---|---|
| Debian / Ubuntu / Mint | `apt install default-mysql-client` |
| RHEL / AlmaLinux / Rocky | `dnf install mysql` |
| Arch / Manjaro | `pacman -S mysql-clients` |
| macOS (Homebrew) | `brew install mysql-client` |

#### `pg_dump` / `psql`

Bundled with PostgreSQL server packages.

| OS | Install |
|---|---|
| Debian / Ubuntu / Mint | `apt install postgresql-client` |
| RHEL / AlmaLinux / Rocky | `dnf install postgresql` |
| Arch / Manjaro | `pacman -S postgresql-libs` |
| macOS (Homebrew) | `brew install libpq` |

#### `sqlite3` CLI

| OS | Install |
|---|---|
| Debian / Ubuntu / Mint | `apt install sqlite3` |
| RHEL / AlmaLinux / Rocky | `dnf install sqlite` |
| Arch / Manjaro | `pacman -S sqlite` |
| macOS | pre-installed on all versions |

#### pgloader

Docs: [pgloader.io](https://pgloader.io/) · Source: [github.com/dimitri/pgloader](https://github.com/dimitri/pgloader) · Licence: PostgreSQL (BSD-like)

| OS | Install |
|---|---|
| Debian / Ubuntu / Mint | `apt install pgloader` |
| Arch / Manjaro | `yay -S pgloader` |
| Docker (any OS) | `docker run --rm -it dimitri/pgloader:latest pgloader <args>` |

#### mysql2sqlite

Download: [github.com/dumblob/mysql2sqlite](https://github.com/dumblob/mysql2sqlite) · Licence: MIT · single shell script, no compiled dependencies.

```bash
curl -LO https://raw.githubusercontent.com/dumblob/mysql2sqlite/master/mysql2sqlite
chmod +x mysql2sqlite
```

#### sqlite3-to-mysql

Download: [github.com/techouse/sqlite3-to-mysql](https://github.com/techouse/sqlite3-to-mysql) · Licence: MIT · Python package, requires Python 3.8+.

```bash
pip install sqlite3-to-mysql
```

---

### 21a. Any → SQLite

#### From MySQL / MariaDB → SQLite

Use the [mysql2sqlite](https://github.com/dumblob/mysql2sqlite) shell script:

```bash
curl -LO https://raw.githubusercontent.com/dumblob/mysql2sqlite/master/mysql2sqlite
chmod +x mysql2sqlite

mysqldump --no-tablespaces --skip-extended-insert --compact \
  -u "$DATABASE_USER" -p"$DATABASE_PASSWORD" \
  -h "$DATABASE_HOST" "$DATABASE_NAME" \
  | ./mysql2sqlite - | sqlite3 var/data_dev.db
```

#### From PostgreSQL → SQLite

Use [pgloader](https://pgloader.io/):

```bash
touch var/data_dev.db

cat > /tmp/pg_to_sqlite.load <<EOF
LOAD DATABASE
  FROM postgresql://db_user:db_pass@127.0.0.1/db_name
  INTO sqlite:///$(pwd)/var/data_dev.db

WITH include no drop, create tables, create indexes, reset sequences

SET work_mem TO '128MB', maintenance_work_mem TO '512MB';
EOF

pgloader /tmp/pg_to_sqlite.load
```

#### After migrating to SQLite — update `.env.local`

```bash
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
MESSENGER_TRANSPORT_DSN=sync://
```

Fix permissions:

```bash
# alpha.se7enx.com (FPM pool: alpha:psacln):
chmod 660 var/data_dev.db && chown alpha:psacln var/data_dev.db
# Generic:
chmod 660 var/data_dev.db && chown $USER:www-data var/data_dev.db

php bin/console cache:clear
```

> **Note:** After migrating to SQLite, verify the composite PK fix has been applied
> to `ezcontentobject_attribute`, `ezcontentclass`, and `ezcontentclass_attribute`.
> If in doubt, test by creating a new draft of any content object in the Admin UI.
> A `UNIQUE constraint failed` error means the fix is needed — see [Section 3b](#3b-sqlite--v3-specific-workarounds).

---

### 21b. SQLite → MySQL / MariaDB

Create the target database first:

```sql
CREATE DATABASE your_db_name
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_520_ci;
GRANT ALL PRIVILEGES ON your_db_name.* TO 'your_db_user'@'localhost'
  IDENTIFIED BY 'your_db_password';
FLUSH PRIVILEGES;
```

Then convert using [sqlite3-to-mysql](https://github.com/techouse/sqlite3-to-mysql) (MIT, Python):

```bash
pip install sqlite3-to-mysql

sqlite3mysql \
  --sqlite-file var/data_dev.db \
  --mysql-database "$DATABASE_NAME" \
  --mysql-user "$DATABASE_USER" \
  --mysql-password "$DATABASE_PASSWORD" \
  --mysql-host "$DATABASE_HOST" \
  --mysql-port 3306 \
  --chunk 1000
```

#### After migrating — update `.env.local`

```bash
DATABASE_DRIVER=pdo_mysql
DATABASE_HOST=127.0.0.1
DATABASE_PORT=3306
DATABASE_NAME=your_db_name
DATABASE_USER=your_db_user
DATABASE_PASSWORD=your_db_password
DATABASE_CHARSET=utf8mb4
DATABASE_COLLATION=utf8mb4_unicode_520_ci
DATABASE_VERSION=mariadb-10.6.0   # or MySQL version e.g. 8.0
# Remove DATABASE_URL=sqlite:// and MESSENGER_TRANSPORT_DSN=sync://
```

---

### 21c. SQLite → PostgreSQL

Use [pgloader](https://pgloader.io/):

```bash
psql -U postgres -c "CREATE DATABASE exponential ENCODING 'UTF8';"

cat > /tmp/sqlite_to_pg.load <<EOF
LOAD DATABASE
  FROM sqlite:///$(pwd)/var/data_dev.db
  INTO postgresql://pg_user:pg_pass@127.0.0.1/exponential

WITH include no drop, create tables, create indexes, reset sequences;
EOF

pgloader /tmp/sqlite_to_pg.load
```

#### After migrating — update `.env.local`

```bash
DATABASE_DRIVER=pdo_pgsql
DATABASE_HOST=127.0.0.1
DATABASE_PORT=5432
DATABASE_NAME=exponential
DATABASE_USER=pg_user
DATABASE_PASSWORD=pg_pass
DATABASE_CHARSET=utf8
DATABASE_VERSION=16
# Remove DATABASE_URL=sqlite:// and MESSENGER_TRANSPORT_DSN=sync://
```

---

### 21d. MySQL / MariaDB → PostgreSQL

Use [pgloader](https://pgloader.io/):

```bash
psql -U postgres -c "CREATE DATABASE exponential ENCODING 'UTF8';"

cat > /tmp/mysql_to_pg.load <<'EOF'
LOAD DATABASE
  FROM    mysql://db_user:db_pass@127.0.0.1/source_db
  INTO    postgresql://pg_user:pg_pass@127.0.0.1/exponential

WITH include no drop,
     create tables,
     create indexes,
     reset sequences,
     foreign keys

SET work_mem TO '128MB'

CAST
  column type matching ~/enum/ to text,
  type tinyint to boolean using tinyint-to-boolean,
  type longtext to text, type mediumtext to text,
  type int with unsigned to bigint;
EOF

pgloader /tmp/mysql_to_pg.load
```

#### After migrating — update `.env.local`

```bash
DATABASE_DRIVER=pdo_pgsql
DATABASE_HOST=127.0.0.1
DATABASE_PORT=5432
DATABASE_NAME=exponential
DATABASE_USER=pg_user
DATABASE_PASSWORD=pg_pass
DATABASE_CHARSET=utf8
DATABASE_VERSION=16
```

---

### 21e. Post-conversion checklist

After any database engine switch, run through every item:

```bash
# 1. Update .env.local with the new DATABASE_URL or database vars
$EDITOR .env.local

# 2. Clear the Symfony container and cache (it caches the DBAL connection)
php bin/console cache:clear

# 3. Validate Doctrine entity mappings against the new DB
php bin/console doctrine:schema:validate

# 4. Run any pending Doctrine migrations
php bin/console doctrine:migration:migrate --allow-no-migration

# 5. Regenerate the search index against the new DB
php bin/console exponential:reindex

# 6. Smoke-test the site
curl -I https://your-site-domain/
curl -I https://your-site-domain/adminui/

# 7. If using SQLite as target — fix file permissions (skip for MySQL/PostgreSQL)
chmod 660 var/data_dev.db
chown alpha:psacln var/data_dev.db   # alpha.se7enx.com; replace with your FPM user
```

#### Common post-conversion issues

| Symptom | Cause | Fix |
|---|---|---|
| `SQLSTATE[42S02]: Base table not found` | Table not migrated | Run `doctrine:schema:validate` and check conversion tool log for errors |
| Binary/blob content garbled | Charset mismatch during export | Re-export with `--default-character-set=utf8mb4` (mysqldump) or `CLIENT_ENCODING=UTF8` (psql) |
| Serialisation failure (PostgreSQL) | Concurrent access during import | Import with `APP_ENV=dev` and no web traffic; use a maintenance window |
| `UNIQUE constraint failed: ezcontentobject_attribute.id` (SQLite target) | Composite PK not applied | See [Section 3b](#3b-sqlite--v3-specific-workarounds) |
| `SQLite attempt to write a readonly database` | Web server user cannot write the `.db` file | `chmod 660 var/data_dev.db && chown $USER:www-data var/data_dev.db` |

> 💾 **Git Save Point — database conversion complete**
> ```bash
> cp .env.local .env.local.bak
> git add .env.local.bak
> git commit -m "chore(db): convert database from <source> to <target>"
> ```

---

## 22. Complete CLI Reference

### 22.1 Symfony Core

```bash
# Discovery
php bin/console list                                           # list all commands
php bin/console help <command>                                 # help for any command

# Cache
php bin/console cache:clear                                    # clear current APP_ENV cache
php bin/console cache:clear --env=prod                         # clear production cache
php bin/console cache:warmup --env=prod                        # warm up production cache
php bin/console cache:pool:clear cache.tagaware.filesystem     # clear a named pool
php bin/console cache:pool:list                                # list cache pools

# Assets
php bin/console assets:install --symlink --relative public     # publish bundle assets

# Routing
php bin/console debug:router                                   # list all routes
php bin/console debug:router <route-name>                      # detail one route
php bin/console router:match /path/to/page                     # which route matches

# Container / Services
php bin/console debug:container                                # list all service IDs
php bin/console debug:container <service-id>                   # show service definition
php bin/console debug:autowiring                               # list autowireable types
php bin/console debug:config <bundle>                          # dump resolved config
php bin/console debug:event-dispatcher                         # list all event listeners

# Twig
php bin/console debug:twig                                     # list Twig extensions
php bin/console lint:twig templates/                           # lint Twig templates
```

### 22.2 Doctrine / Migrations

```bash
php bin/console doctrine:migration:migrate --allow-no-migration    # run pending migrations
php bin/console doctrine:migration:migrate --dry-run               # preview SQL only
php bin/console doctrine:migration:status                          # show status
php bin/console doctrine:migration:diff                            # generate migration
php bin/console doctrine:schema:validate                           # validate mappings
php bin/console doctrine:schema:update --dump-sql                  # preview schema changes
php bin/console doctrine:database:create                           # create the database
```

### 22.3 Platform v3 — `exponential:` Commands

```bash
# Install — initial database setup (run ONCE on a fresh install)
php bin/console exponential:install exponential-media --no-interaction
# Alternate install types:
php bin/console exponential:install exponential-oss --no-interaction

# Search index
php bin/console exponential:reindex                                # full reindex
php bin/console exponential:reindex --iteration-count=100         # batched
php bin/console exponential:reindex --content-type=article        # one content type

# Cron
php bin/console ibexa:cron:run                                     # run cron scheduler
# alias: php bin/console ezplatform:cron:run

# GraphQL
php bin/console ibexa:graphql:generate-schema                      # regenerate schema
# alias: php bin/console ezplatform:graphql:generate-schema

# HTTP cache
php bin/console fos:httpcache:invalidate:path / --all              # purge all paths
php bin/console fos:httpcache:invalidate:tag <tag>                 # purge by tag

# Image variations
php bin/console liip:imagine:cache:remove                          # remove all
php bin/console liip:imagine:cache:remove --filter=small           # one filter alias

# Content utilities
php bin/console ibexa:content:cleanup-versions                    # prune old versions
# alias: php bin/console ezplatform:content:cleanup-versions
php bin/console ibexa:urls:regenerate-aliases                      # rebuild URL aliases
php bin/console ibexa:copy-subtree <src-id> <dst-id>              # copy a subtree

# Full config dump
php bin/console debug:config ezpublish                             # dump eZ config
```

### 22.4 Frontend / Asset Build (Yarn / Webpack Encore)

```bash
# Node version management — REQUIRED FIRST
source ~/.nvm/nvm.sh && nvm use 18    # activate Node.js 18 (or nvm use 20)
corepack enable                        # activates Yarn 1.22.x

# Dependencies
yarn install                           # install / sync all Node dependencies
yarn upgrade                           # upgrade within semver constraints
yarn add <package>                     # add a new dependency
yarn remove <package>                  # remove a dependency

# Site asset builds
yarn build:dev                         # build with source maps (development)
yarn build:prod                        # build minified (production)
yarn watch                             # watch mode — auto-rebuild on change
yarn start                             # webpack dev server

# Admin UI asset builds
yarn ez                                # builds eZ/Ibexa admin UI assets (production)

# Code quality
yarn format:js                         # format JS with Prettier
yarn linter:js                         # lint JS with ESLint
```

### 22.5 Composer Maintenance

```bash
# Install / update
COMPOSER_ALLOW_SUPERUSER=1 composer install                    # install from lock file
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev -o        # production
COMPOSER_ALLOW_SUPERUSER=1 composer update                     # update all within constraints
COMPOSER_ALLOW_SUPERUSER=1 composer update se7enxweb/site-bundle  # update one package
COMPOSER_ALLOW_SUPERUSER=1 composer update --dry-run           # preview

# Autoloader
composer dump-autoload                 # regenerate autoloader
composer dump-autoload -o              # optimised (production)

# Info / Audit
composer show                          # list all installed packages
composer show se7enxweb/site-bundle    # detail one package
composer outdated                      # list outdated packages
composer audit                         # check for security advisories
composer validate                      # validate composer.json / composer.lock
```

### 22.6 Symfony CLI

```bash
symfony server:start                   # start HTTPS dev server (https://127.0.0.1:8000)
symfony server:start -d                # start in background daemon mode
symfony server:stop                    # stop background server
symfony server:log                     # tail server access/error log
symfony server:status                  # show server status + URL

symfony check:requirements             # verify PHP + extension requirements
symfony check:security                 # audit composer.lock for known CVEs
symfony local:php:list                 # list PHP versions available via Symfony CLI
```

### 22.7 Git Workflow

```bash
# Branching
git checkout -b feature/my-feature    # new feature branch
git checkout 1.1.0.x                  # switch to the active branch

# Save Points
git add -A && git commit -m "chore: <description>"
git stash                              # save uncommitted work
git stash pop                          # restore stashed work

# Tags (deploy markers)
git tag -a "deploy-$(date +%Y%m%d-%H%M)" -m "deploy $(date)"
git push origin --tags

# Inspection
git log --oneline -20                  # last 20 commits
git diff HEAD                          # uncommitted changes
git status                             # working tree status
```

---

## 23. Git SSH Configuration (se7enxweb account)

All 7x-maintained fork repositories are hosted on GitHub under the `se7enxweb`
organisation. If you need to push to any of these repos from the server, the SSH
alias `github-as-7x` is used instead of `github.com` to authenticate as the
`se7enxweb` user.

### `~/.ssh/config` entry

```sshconfig
Host github-as-7x
    HostName github.com
    User git
    IdentityFile ~/.ssh/id_rsa_se7enxweb    # path to the se7enxweb private key
    IdentitiesOnly yes
```

### Clone / push using the alias

```bash
# Clone a se7enxweb repo using the SSH alias:
git clone git@github-as-7x:se7enxweb/site-bundle.git

# Add a remote for an existing clone:
git remote add se7enxweb git@github-as-7x:se7enxweb/site-bundle.git

# Push to the se7enxweb remote:
git push se7enxweb se7enxweb-2.0.x:master
```

### Fork repositories and their purposes

| Fork | Remote URL | Replaces | Active branch |
|---|---|---|---|
| `se7enxweb/site-bundle` | `git@github-as-7x:se7enxweb/site-bundle.git` | `netgen/site-bundle` | `2.x` |
| `se7enxweb/oss` | `git@github-as-7x:se7enxweb/oss.git` | — (metapackage) | `master` |
| `se7enxweb/exponential-platform-nexus` | `git@github.com:se7enxweb/exponential-platform-nexus.git` | — (this project) | `1.1.0.x` |

---

Copyright © 1998–2026 7x (se7enx.com). All rights reserved unless otherwise noted.
Exponential Platform Nexus is Open Source software. See [LICENSE](../../LICENSE) and
[LICENSE-bul](../../LICENSE-bul) for the full licence texts.
