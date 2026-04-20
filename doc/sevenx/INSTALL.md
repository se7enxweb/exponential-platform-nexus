# Exponential Platform Nexus v4 — 7x Installation & Operations Guide

> **Exponential Platform Nexus v4** is the 7x fork and customisation of the Netgen
> Media Site skeleton for Ibexa DXP v4 OSS. It runs on **Symfony 5.4 LTS** with
> **PHP 8.2+** (PHP 8.5 recommended) and includes the `se7enxweb/site-bundle` 3.x fork
> (replacing `netgen/site-bundle`) and the `ExponentialSqliteGateway` SQLite workaround
> for correct ID generation on SQLite databases.
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
> `ibexa:graphql:generate-schema`).

---

## Table of Contents

1. [Requirements](#1-requirements)
2. [Architecture Overview](#2-architecture-overview)
3. [7x Customisations — What They Fix and Why](#3-7x-customisations--what-they-fix-and-why)
   - [3a. `se7enxweb/site-bundle` 3.x fork](#3a-se7enxwebsite-bundle-3x-fork)
   - [3b. SQLite — v4-specific workarounds](#3b-sqlite--v4-specific-workarounds)
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
10. [Admin UI Assets (Platform v4 Admin UI)](#10-admin-ui-assets-platform-v4-admin-ui)
11. [JWT Authentication (REST API)](#11-jwt-authentication-rest-api)
12. [GraphQL Schema](#12-graphql-schema)
13. [Search Index](#13-search-index)
14. [Image Variations](#14-image-variations)
15. [Cache Management](#15-cache-management)
16. [Day-to-Day Operations: Start / Stop / Restart](#16-day-to-day-operations-start--stop--restart)
17. [Updating the Codebase](#17-updating-the-codebase)
18. [Cron Jobs](#18-cron-jobs)
19. [Solr Search Engine (optional)](#19-solr-search-engine-optional)
20. [Varnish HTTP Cache (optional)](#20-varnish-http-cache-optional)
21. [Troubleshooting](#21-troubleshooting)
22. [Database Conversion](#22-database-conversion)
23. [Complete CLI Reference](#23-complete-cli-reference)
24. [Git SSH Configuration (se7enxweb account)](#24-git-ssh-configuration-se7enxweb-account)

---

## 1. Requirements

### PHP

- **PHP 8.2+** (PHP 8.5 strongly recommended — the server at `alpha.se7enx.com` runs PHP 8.5.5)
- Required extensions: `gd` or `imagick`, `curl`, `json`, `pdo_mysql` or `pdo_pgsql` or `pdo_sqlite`,
  `xsl`, `xml`, `intl`, `mbstring`, `opcache`, `ctype`, `iconv`
- For SQLite: `pdo_sqlite` + `sqlite3` PHP extensions (usually bundled with PHP;
  verify with `php -m | grep -i sqlite`)
- `memory_limit` ≥ 256M (512M recommended)
- `date.timezone` must be set in `php.ini`
- `max_execution_time` ≥ 120 (300 recommended for CLI operations)

> **PHP 8.2 is the minimum** for this project. The `composer.json` declares `"php": ">=8.2"`.

### Web Server

- **Apache 2.4** with `mod_rewrite`, `mod_deflate`, `mod_headers`, `mod_expires` enabled; run in
  `event` or `worker` mode with PHP-FPM _or_
- **Nginx 1.18+** with PHP-FPM

The server at `alpha.se7enx.com` runs PHP-FPM under pool `alpha:psacln`. All
`php bin/console` commands run as root but the FPM worker runs as `alpha:psacln`.
The SQLite database file (`var/data.db`) must be owned `alpha:psacln` with mode
`660` for FPM to write it.

### Node.js & Yarn

- [Node.js](https://nodejs.org/en/download/) **18** — managed via
  [nvm](https://github.com/nvm-sh/nvm) (strongly recommended)
- [Yarn](https://classic.yarnpkg.com/en/docs/install) **1.22.x** — activated via
  [corepack](https://github.com/nodejs/corepack) `enable` after `nvm use 18`

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

## 2. Architecture Overview

Exponential Platform Nexus v4 is a **single-kernel Symfony 5.4 LTS application** with:

```
Browser Request
      │
      ▼
   Web Server (Apache / Nginx)
      │
      ▼
  public/index.php  ── Symfony Kernel (Platform v4 OSS — Symfony 5.4 LTS)
      │
      ├── URI: /adminui/**           → Platform v4 Admin UI (React)       ← siteaccess: admin
      ├── URI: /api/ezp/v2/**        → REST API v2 (JWT-auth)             ← siteaccess: admin
      ├── URI: /graphql              → GraphQL API                        ← siteaccess: admin
      ├── URI: /nglayouts/**         → Netgen Layouts admin + app         ← siteaccess: admin
      └── URI: /**                   → Platform v4 Twig/Symfony Front End ← siteaccess: site
                                           Symfony controllers + Twig templates
```

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
│   ├── packages/                   Symfony/Ibexa/Netgen package config
│   ├── routes/                     Route inclusions (including netgen_layouts.yaml)
│   └── app/services.yaml           Application service definitions (incl. ExponentialSqliteGateway)
├── src/
│   ├── Controller/                 Symfony controllers
│   ├── Entity/                     Doctrine entities
│   ├── Persistence/Legacy/SharedGateway/  ExponentialSqliteGateway (SQLite ID fix)
│   ├── RepositoryInstaller/        exponential:install command + ExponentialMediaInstaller
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
│   └── data.db                     SQLite database (default for dev)
├── data/
│   ├── sqlite/media_schema.sql     Extra schema tables (nglayouts, etc.) for SQLite
│   ├── sqlite/media_data.sql       Demo seed data (SQLite-compatible INSERT statements)
│   ├── mysql/media_data.sql        Demo seed data (MySQL)
│   └── postgresql/media_data.sql   Demo seed data (PostgreSQL)
├── composer.json                   Declares se7enxweb/oss ~4.6.0
├── package.json                    Declares Node.js 18 engine requirement
└── webpack.config.js               Webpack Encore config (ibexa + project builds)
```

### Siteaccesses

| Siteaccess | URL prefix | Purpose |
|---|---|---|
| `site` | `/` | Symfony/Twig public front end |
| `admin` | `/adminui/` | Platform v4 Admin UI (React) + REST API + GraphQL |

---

## 3. 7x Customisations — What They Fix and Why

This section is the canonical technical reference for every customisation applied by 7x
to this v4 project. Read this before debugging any error.

### 3a. `se7enxweb/site-bundle` 3.x fork

- **Replaces**: `netgen/site-bundle` (via Composer `replace` directive)
- **GitHub**: https://github.com/se7enxweb/site-bundle
- **Packagist**: https://packagist.org/packages/se7enxweb/site-bundle
- **Active branch / tag**: `3.0.x` (tag `3.0.6`)
- **Installed via**: directly in `composer.json` as `"se7enxweb/site-bundle": "~3.0.6"`

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

The `se7enxweb/site-bundle` 3.x fork provides the v4-compatible version of the Netgen
site bundle maintained by 7x. It ensures compatibility with `se7enxweb/oss ~4.6.0`
(the Ibexa DXP v4 metapackage) and receives security and compatibility updates from 7x.

---

### 3b. SQLite — v4-specific workarounds

> **This section is critical for SQLite users.** Skip if using MySQL/MariaDB or PostgreSQL
> — these workarounds are no-ops on those databases.

Two separate SQLite-specific problems exist in Ibexa DXP v4 that are fixed in this project:

#### Problem 1 — ID collision: `SqliteGateway::getColumnNextIntegerValue()` uses `hrtime()`

The upstream `SqliteGateway` generates new IDs using `hrtime(true) % PHP_INT_MAX`, which
produces large unpredictable integers. When a SQLite database has been seeded with
real content (which uses sequential low-integer IDs), the `hrtime()` values frequently
**collide** with existing rows, causing `UNIQUE constraint failed` errors.

**Fix — `ExponentialSqliteGateway`:**

Located at `src/Persistence/Legacy/SharedGateway/ExponentialSqliteGateway.php`.
Wired via DI in `config/app/services.yaml`:

```yaml
Ibexa\Core\Persistence\Legacy\SharedGateway\DatabasePlatform\SqliteGateway:
    class: App\Persistence\Legacy\SharedGateway\ExponentialSqliteGateway
    arguments:
        $connection: '@ibexa.persistence.connection'
    tags:
        - { name: ibexa.storage.legacy.gateway.shared, platform: sqlite }
```

The override uses `SELECT COALESCE(MAX(column), 0) + 1 FROM table` to guarantee the
next ID is always higher than any existing value in the table:

```php
public function getColumnNextIntegerValue(string $tableName, string $columnName, int $incrementBy = 1): int
{
    return (int) $this->connection
        ->executeQuery("SELECT COALESCE(MAX($columnName), 0) + 1 FROM $tableName")
        ->fetchOne();
}
```

#### Problem 2 — Composite PK collapsed to single PK by SQLite Doctrine schema

Ibexa's schema defines three tables with `PRIMARY KEY (id, version)` (composite):
- `ezcontentobject_attribute`
- `ezcontentclass`
- `ezcontentclass_attribute`

When Doctrine creates these tables on SQLite via `CREATE TABLE`, it collapses the
`INTEGER` column in a composite PK to a plain `INTEGER` (not `INTEGER PRIMARY KEY`).
SQLite then enforces `id` alone as unique, which breaks Ibexa's versioning model: when
a new draft is created, `insertExistingField()` reuses the same `id` with a new
`version` — valid on MySQL/PostgreSQL (composite PK), but fails on SQLite with:

```
UNIQUE constraint failed: ezcontentobject_attribute.id
```

**Fix — `fixSqliteCompositePrimaryKeys()` in `ExponentialMediaInstaller`:**

Located in `src/RepositoryInstaller/Installer/ExponentialMediaInstaller.php`.
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
- **Branch**: version `~4.6.0`

This is the **central metapackage** that wires the entire Exponential Platform v4 DXP
dependency graph together. It:

- Requires all Ibexa DXP v4 OSS packages
- Requires all Symfony 5.4 LTS packages
- Requires all supporting Doctrine, Twig, and Symfony ecosystem packages

**You should not require Ibexa core packages directly in this project's `composer.json`.**
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
git checkout 1.2.0.x
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

#### Step 5 — Build frontend assets (Node.js 18 required)

```bash
source ~/.nvm/nvm.sh && nvm use 18    # REQUIRED — do not skip this
corepack enable
yarn install
yarn build:prod
```

#### Step 6 — Build Admin UI assets

```bash
php bin/console assets:install --symlink --relative public
yarn ibexa:build
```

#### Step 7 — Generate JWT keypair

```bash
php bin/console lexik:jwt:generate-keypair
```

#### Step 8 — Generate GraphQL schema

```bash
php bin/console ibexa:graphql:generate-schema
```

#### Step 9 — Clear all caches

```bash
php bin/console cache:clear
```

#### Step 10 — Reindex search

```bash
php bin/console exponential:reindex
```

> 💾 **Git Save Point 2 — Installation complete**
> ```bash
> git add -A && git commit -m "chore(install): exponential-platform-nexus install complete"
> ```

#### Step 11 — Start the dev server

```bash
symfony server:start
```

All access points after install:

| URL | Description |
|---|---|
| `https://127.0.0.1:8000/` | Platform v4 Symfony/Twig public site |
| `https://127.0.0.1:8000/adminui/` | **Platform v4 Admin UI** (React) |
| `https://127.0.0.1:8000/api/ezp/v2/` | REST API v2 |
| `https://127.0.0.1:8000/graphql` | GraphQL endpoint |
| `https://127.0.0.1:8000/nglayouts/admin` | Netgen Layouts admin |
| `https://127.0.0.1:8000/nglayouts/app` | Netgen Layouts app editor |

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

# JWT (REST API authentication)
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=<random-64-char-hex-string>
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

```bash
# In .env.local — choose one of:
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
# or with environment-specific filename:
# DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"

# Required for SQLite — the doctrine transport requires a second DB connection
# that SQLite cannot support; sync:// processes messages synchronously instead:
MESSENGER_TRANSPORT_DSN=sync://
```

**SQLite file permissions (critical):**

The install command typically runs as your shell user (or root). The web server /
PHP-FPM pool must have write access to the `.db` file:

```bash
# On alpha.se7enx.com (PHP-FPM pool: alpha:psacln):
chmod 660 var/data.db
chown alpha:psacln var/data.db

# Generic (adjust www-data to your FPM user):
chmod 660 var/data.db
chown $USER:www-data var/data.db
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
SEARCH_ENGINE=legacy       # default — uses the Ibexa legacy search engine
# SEARCH_ENGINE=solr       # use Solr (see Section 19)
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
all schema and seed data, and applies the composite PK fix (see [Section 3b](#3b-sqlite--v4-specific-workarounds)).

#### Step 1 — Verify PHP extensions

```bash
php -m | grep -i sqlite
# Expected:
#   SQLite3
#   pdo_sqlite
```

#### Step 2 — Configure `.env.local`

```bash
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
MESSENGER_TRANSPORT_DSN=sync://
```

Remove or comment out any `DATABASE_DRIVER`, `DATABASE_HOST`, `DATABASE_PORT`,
`DATABASE_NAME`, `DATABASE_USER`, `DATABASE_PASSWORD` lines.

#### Step 3 — Run the install command

```bash
php bin/console exponential:install exponential-media --no-interaction
```

This single command:
1. Creates `var/data.db` (the SQLite database file)
2. Imports the Ibexa platform schema
3. Applies `fixSqliteCompositePrimaryKeys()` to set correct `PRIMARY KEY (id, version)` on three tables
4. Imports extra schema (`data/sqlite/media_schema.sql`)
5. Imports seed content data (`data/sqlite/media_data.sql` — 11,999 inserts)
6. Creates all Doctrine ORM tables

Default credentials after install: **`admin` / `publish`**

#### Step 4 — Fix file permissions

The web server / FPM must be able to write the `.db` file:

```bash
# alpha.se7enx.com specific:
chmod 660 var/data.db
chown alpha:psacln var/data.db

# Generic:
chmod 660 var/data.db
chown $USER:www-data var/data.db   # replace with your FPM user
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
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
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

# Platform v4 public var directory (thumbnails, generated content)
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

The project uses Webpack Encore + Yarn. **Node.js 18 is required.**

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

## 10. Admin UI Assets (Platform v4 Admin UI)

The Platform v4 Admin UI assets (React components, SCSS, icons) are built separately from
the site frontend. They are not rebuilt automatically on `composer install`.

### Prerequisites

The `var/encore/` directory must be populated by `assets:install` before any
`ibexa:*` build can run:

```bash
php bin/console assets:install --symlink --relative public
```

This publishes bundle `public/` directories to `public/bundles/` and writes the
`var/encore/ibexa.config.js`, `var/encore/ibexa.config.setup.js`,
and `var/encore/ibexa.config.manager.js` files.

### Build Admin UI assets — production

```bash
nvm use 18 && yarn ibexa:build
```

### Build Admin UI assets — development (with source maps)

```bash
nvm use 18 && yarn ibexa:dev
```

### Watch mode

```bash
nvm use 18 && yarn ibexa:watch
```

### Dump JS translation assets (required for Admin UI i18n)

```bash
php bin/console bazinga:js-translation:dump public/assets --merge-domains
```

### What changes require an Admin UI asset rebuild

| Change | Rebuild needed |
|---|---|
| `composer update` pulled a new bundle version | Yes — `yarn ibexa:build` |
| Any bundle's `Resources/public/` JS or SCSS changed | Yes — `yarn ibexa:build` |
| `webpack.config.js` modified | Yes — `yarn ibexa:build` |
| Admin RichText editor configuration changed | Yes — `yarn ibexa:build` |
| Translation strings changed | Yes — dump translations |

---

## 11. JWT Authentication (REST API)

JWT keypairs are required for the REST API to function. They are git-ignored and must
be generated on every fresh install:

```bash
php bin/console lexik:jwt:generate-keypair
# Writes:
#   config/jwt/private.pem
#   config/jwt/public.pem
```

On key rotation:

```bash
php bin/console lexik:jwt:generate-keypair --overwrite
php bin/console cache:clear
```

Back up `config/jwt/private.pem` and `config/jwt/public.pem` securely. If lost,
all existing tokens are invalidated and clients must re-authenticate.

---

## 12. GraphQL Schema

The GraphQL schema is auto-generated from the content type model. Regenerate it after
any content type or field type changes:

```bash
php bin/console ibexa:graphql:generate-schema
php bin/console cache:clear
```

The GraphQL endpoint is at `/graphql`. The GraphiQL browser UI is at
`/graphql/explorer` when `APP_ENV=dev`.

---

## 13. Search Index

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

## 14. Image Variations

Image variations are generated on demand by Liip Imagine. Configuration lives in
`config/packages/ibexa.yaml` under `ibexa.system.<siteaccess>.image_variations`.

### Clear generated variation cache

```bash
php bin/console liip:imagine:cache:remove
php bin/console cache:clear
```

---

## 15. Cache Management

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

## 16. Day-to-Day Operations: Start / Stop / Restart

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

```bash
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

# 5. Rebuild Platform v4 Admin UI assets (if admin-ui bundle updated)
source ~/.nvm/nvm.sh && nvm use 18 && yarn ibexa:build

# 6. Rebuild frontend site assets (if theme/JS/CSS changed)
yarn build:prod

# 7. Dump JS translations
php bin/console bazinga:js-translation:dump public/assets --merge-domains --env=prod

# 8. Clear & warm up caches
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Fix cache permissions if running as root but FPM is a different user:
chmod -R a+rX var/cache/

# 9. Reindex search (if content model changed)
# php bin/console exponential:reindex --env=prod
```

> 💾 **Git Save Point — After each production deploy**
> ```bash
> git tag -a "deploy-$(date +%Y%m%d-%H%M)" -m "production deploy $(date)"
> git push origin --tags
> ```

---

## 17. Updating the Codebase

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

## 18. Cron Jobs

Add to crontab (`crontab -e -u www-data` or for the FPM user):

```bash
# Platform v4 cron runner (every 5 minutes)
*/5 * * * * /usr/bin/php /path/to/project/bin/console ibexa:cron:run --env=prod >> /var/log/nexus-cron.log 2>&1
```

---

## 19. Solr Search Engine (optional)

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

## 20. Varnish HTTP Cache (optional)

1. Set env vars in `.env.local`:
   ```bash
   HTTPCACHE_PURGE_TYPE=varnish
   HTTPCACHE_PURGE_SERVER=http://127.0.0.1:6081
   HTTPCACHE_VARNISH_INVALIDATE_TOKEN=<your-secret>
   TRUSTED_PROXIES=127.0.0.1
   ```
2. Set `APP_HTTP_CACHE=0` in your web server vhost (let Varnish handle caching)
3. Load the Platform v4 Varnish VCL from `doc/varnish/` (if present)
4. Clear cache:
   ```bash
   php bin/console cache:clear
   php bin/console fos:httpcache:invalidate:path / --all
   ```

---

## 21. Troubleshooting

### HTTP 500 — "no such table: ibexa_section" or similar schema errors

The database was not initialised. Run the installer:

```bash
php bin/console exponential:install exponential-media --no-interaction
# Then fix permissions for SQLite:
chmod 660 var/data.db && chown alpha:psacln var/data.db
```

### SQLite — `UNIQUE constraint failed: ezcontentobject_attribute.id`

This is the composite PK issue. It occurs when the database was initialised without
the `fixSqliteCompositePrimaryKeys()` fix (e.g. an old install or a manual schema
import). To fix on an existing database:

1. Re-run the installer on a fresh database (recommended):
   ```bash
   rm var/data.db
   php bin/console exponential:install exponential-media --no-interaction
   chmod 660 var/data.db && chown alpha:psacln var/data.db
   ```

2. Or apply the fix manually in `sqlite3`:
   ```bash
   sqlite3 var/data.db
   ```
   Recreate each of the three tables with `PRIMARY KEY (id, version)` using the
   rename-swap pattern (see Section 3b for details).

### SQLite — `attempt to write a readonly database`

The FPM user cannot write `var/data.db`. Fix:

```bash
# alpha.se7enx.com:
chmod 660 var/data.db
chown alpha:psacln var/data.db

# Generic:
chmod 660 var/data.db
chown $USER:www-data var/data.db
```

### Cache not clearing — permission denied on `var/cache/`

The cache was written by root but FPM reads as `alpha:psacln`. Fix:

```bash
rm -rf var/cache/dev/* var/cache/prod/*
php bin/console cache:warmup
chmod -R a+rX var/cache/
```

### "Class not found" after `composer update`

```bash
COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload -o
php bin/console cache:clear
```

### Assets not loading (404 on `/bundles/` or `/assets/`)

```bash
php bin/console assets:install --symlink --relative public
yarn build:prod         # rebuild site assets
yarn ibexa:build        # rebuild Admin UI assets
```

### Search results outdated

```bash
php bin/console exponential:reindex
```

### Image variations missing

```bash
php bin/console liip:imagine:cache:remove
php bin/console cache:clear
# Variations regenerate on next request
```

### JWT authentication errors (REST API)

```bash
php bin/console lexik:jwt:generate-keypair --overwrite
php bin/console cache:clear
```

### `yarn ibexa:build` fails with "Module not found" or Node version error

```bash
# Check Node.js version:
node --version    # must be v18.x.x

# Activate Node 18 if needed:
nvm use 18

# Ensure var/encore/ is populated:
php bin/console assets:install --symlink --relative public

# If still failing, reinstall node_modules:
rm -rf node_modules yarn.lock
yarn install
yarn ibexa:build
```

---

## 22. Database Conversion

This section covers converting an existing, running Exponential Platform Nexus v4
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
> cp var/data.db var/data.db.bak
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

### 22a. Any → SQLite

#### From MySQL / MariaDB → SQLite

Use the [mysql2sqlite](https://github.com/dumblob/mysql2sqlite) shell script:

```bash
curl -LO https://raw.githubusercontent.com/dumblob/mysql2sqlite/master/mysql2sqlite
chmod +x mysql2sqlite

mysqldump --no-tablespaces --skip-extended-insert --compact \
  -u "$DATABASE_USER" -p"$DATABASE_PASSWORD" \
  -h "$DATABASE_HOST" "$DATABASE_NAME" \
  | ./mysql2sqlite - | sqlite3 var/data.db
```

#### From PostgreSQL → SQLite

Use [pgloader](https://pgloader.io/):

```bash
touch var/data.db

cat > /tmp/pg_to_sqlite.load <<EOF
LOAD DATABASE
  FROM postgresql://db_user:db_pass@127.0.0.1/db_name
  INTO sqlite:///$(pwd)/var/data.db

WITH include no drop, create tables, create indexes, reset sequences

SET work_mem TO '128MB', maintenance_work_mem TO '512MB';
EOF

pgloader /tmp/pg_to_sqlite.load
```

#### After migrating to SQLite — update `.env.local`

```bash
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
MESSENGER_TRANSPORT_DSN=sync://
```

Fix permissions:

```bash
# alpha.se7enx.com (FPM pool: alpha:psacln):
chmod 660 var/data.db && chown alpha:psacln var/data.db
# Generic:
chmod 660 var/data.db && chown $USER:www-data var/data.db

php bin/console cache:clear
```

> **Note:** After migrating to SQLite, verify the composite PK fix has been applied
> to `ezcontentobject_attribute`, `ezcontentclass`, and `ezcontentclass_attribute`.
> If in doubt, test by creating a new draft of any content object in the Admin UI.
> A `UNIQUE constraint failed` error means the fix is needed — see [Section 3b](#3b-sqlite--v4-specific-workarounds).

---

### 22b. SQLite → MySQL / MariaDB

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
  --sqlite-file var/data.db \
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

### 22c. SQLite → PostgreSQL

Use [pgloader](https://pgloader.io/):

```bash
psql -U postgres -c "CREATE DATABASE exponential ENCODING 'UTF8';"

cat > /tmp/sqlite_to_pg.load <<EOF
LOAD DATABASE
  FROM sqlite:///$(pwd)/var/data.db
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

### 22d. MySQL / MariaDB → PostgreSQL

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

### 22e. PostgreSQL → MySQL / MariaDB

#### Step 1 — Export each table as CSV from PostgreSQL

```bash
TARGET_DIR=/tmp/pg_csv_export
mkdir -p "$TARGET_DIR"

TABLES=$(psql -U pg_user -d exponential -t \
  -c "SELECT tablename FROM pg_tables WHERE schemaname='public' ORDER BY tablename;")

for TABLE in $TABLES; do
  TABLE=$(echo "$TABLE" | xargs)
  psql -U pg_user -d exponential \
    -c "\COPY \"$TABLE\" TO '$TARGET_DIR/$TABLE.csv' WITH (FORMAT csv, HEADER true, NULL '\\N');"
done
```

#### Step 2 — Create MySQL schema with pgloader (schema only)

```bash
cat > /tmp/schema_only.load <<'EOF'
LOAD DATABASE
  FROM      postgresql://pg_user:pg_pass@127.0.0.1/exponential
  INTO      mysql://db_user:db_pass@127.0.0.1/target_db

WITH include no drop, create tables, no data;
EOF
pgloader /tmp/schema_only.load
```

#### Step 3 — Import CSVs into MySQL

```bash
for CSV in "$TARGET_DIR"/*.csv; do
  TABLE=$(basename "$CSV" .csv)
  mysql --local-infile=1 \
    -u db_user -pdb_pass target_db \
    -e "LOAD DATA LOCAL INFILE '$CSV'
        INTO TABLE \`$TABLE\`
        FIELDS TERMINATED BY ','
        OPTIONALLY ENCLOSED BY '\"'
        LINES TERMINATED BY '\n'
        IGNORE 1 ROWS;"
done
```

#### After migrating — update `.env.local`

```bash
DATABASE_DRIVER=pdo_mysql
DATABASE_HOST=127.0.0.1
DATABASE_PORT=3306
DATABASE_NAME=target_db
DATABASE_USER=db_user
DATABASE_PASSWORD=db_pass
DATABASE_CHARSET=utf8mb4
DATABASE_COLLATION=utf8mb4_unicode_520_ci
DATABASE_VERSION=mariadb-10.6.0
```

---

### 22f. Any → Oracle (export only)

Oracle XE (Express Edition) is free to use but **not open-source**. The recommended
free/open-source path for Oracle targets:

1. Use [ora2pg](https://github.com/darold/ora2pg) (GPL v3) to migrate **Oracle → PostgreSQL** first
2. Then use **22d** or **22e** to reach MySQL/MariaDB if needed

```bash
# Install ora2pg (Perl, GPL v3)
# Debian / Ubuntu:
apt install ora2pg

# Export Oracle schema + data to PostgreSQL-compatible SQL
ora2pg -c /etc/ora2pg/ora2pg.conf -t TABLE -o schema.sql
ora2pg -c /etc/ora2pg/ora2pg.conf -t INSERT -o data.sql

# Import into PostgreSQL
psql -U pg_user -d exponential -f schema.sql
psql -U pg_user -d exponential -f data.sql
```

See [ora2pg.darold.net](https://ora2pg.darold.net/) for full documentation.

---

### 22g. Post-conversion checklist

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
chmod 660 var/data.db
chown alpha:psacln var/data.db   # alpha.se7enx.com; replace with your FPM user
```

#### Common post-conversion issues

| Symptom | Cause | Fix |
|---|---|---|
| `SQLSTATE[42S02]: Base table not found` | Table not migrated | Run `doctrine:schema:validate` and check conversion tool log for errors |
| Binary/blob content garbled | Charset mismatch during export | Re-export with `--default-character-set=utf8mb4` (mysqldump) or `CLIENT_ENCODING=UTF8` (psql) |
| Serialisation failure (PostgreSQL) | Concurrent access during import | Import with `APP_ENV=dev` and no web traffic; use a maintenance window |
| `UNIQUE constraint failed: ezcontentobject_attribute.id` (SQLite target) | Composite PK not applied | See [Section 3b](#3b-sqlite--v4-specific-workarounds) |
| `SQLite attempt to write a readonly database` | Web server user cannot write the `.db` file | `chmod 660 var/data.db && chown $USER:www-data var/data.db` |

> 💾 **Git Save Point — database conversion complete**
> ```bash
> cp .env.local .env.local.bak
> git add .env.local.bak
> git commit -m "chore(db): convert database from <source> to <target>"
> ```

---

## 23. Complete CLI Reference

### 23.1 Symfony Core

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

# JWT
php bin/console lexik:jwt:generate-keypair                     # generate RSA keypair
php bin/console lexik:jwt:generate-keypair --overwrite         # rotate keypair
```

### 23.2 Doctrine / Migrations

```bash
php bin/console doctrine:migration:migrate --allow-no-migration    # run pending migrations
php bin/console doctrine:migration:migrate --dry-run               # preview SQL only
php bin/console doctrine:migration:status                          # show status
php bin/console doctrine:migration:diff                            # generate migration
php bin/console doctrine:schema:validate                           # validate mappings
php bin/console doctrine:schema:update --dump-sql                  # preview schema changes
php bin/console doctrine:database:create                           # create the database
```

### 23.3 Platform v4 — `exponential:` Commands

```bash
# Install — initial database setup (run ONCE on a fresh install)
php bin/console exponential:install exponential-media --no-interaction
# Alternate install types:
php bin/console exponential:install exponential-oss --no-interaction
php bin/console exponential:install ibexa-oss --no-interaction

# Search index
php bin/console exponential:reindex                                # full reindex
php bin/console exponential:reindex --iteration-count=100         # batched
php bin/console exponential:reindex --content-type=article        # one content type
php bin/console exponential:reindex --subtree=45                  # one subtree
php -d memory_limit=-1 bin/console exponential:reindex --env=prod # production (no limit)

# Cron
php bin/console ibexa:cron:run                                     # run cron scheduler
php bin/console ibexa:cron:run --quiet                             # suppress output

# GraphQL
php bin/console ibexa:graphql:generate-schema                      # regenerate schema

# HTTP cache
php bin/console fos:httpcache:invalidate:path / --all              # purge all paths
php bin/console fos:httpcache:invalidate:tag <tag>                 # purge by tag

# Admin UI translations
php bin/console bazinga:js-translation:dump public/assets --merge-domains

# Image variations
php bin/console liip:imagine:cache:remove                          # remove all
php bin/console liip:imagine:cache:remove --filter=small           # one filter alias

# Content utilities
php bin/console exponential:content:cleanup-versions              # prune old versions
php bin/console exponential:urls:regenerate-aliases               # rebuild URL aliases
php bin/console exponential:copy-subtree <src-id> <dst-id>        # copy a subtree

# User management
php bin/console exponential:user:expire-password --force          # expire all passwords
php bin/console exponential:user:validate-password-hashes         # audit hash algos

# Full config dump
php bin/console debug:config ibexa                                 # dump Ibexa config
```

### 23.4 Frontend / Asset Build (Yarn / Webpack Encore)

```bash
# Node version management — REQUIRED FIRST
source ~/.nvm/nvm.sh && nvm use 18    # activate Node.js 18
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
yarn ibexa:dev                         # build Platform v4 Admin UI — dev mode
yarn ibexa:build                       # build Platform v4 Admin UI — production
yarn ibexa:watch                       # watch Admin UI assets

# Code quality
yarn format:js                         # format JS with Prettier
yarn linter:js                         # lint JS with ESLint
```

### 23.5 Composer Maintenance

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

### 23.6 Symfony CLI

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

### 23.7 Git Workflow

```bash
# Branching
git checkout -b feature/my-feature    # new feature branch
git checkout 1.2.0.x                  # switch to the active branch

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

## 24. Git SSH Configuration (se7enxweb account)

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
git push se7enxweb se7enxweb-3.0.x:master
```

### Fork repositories and their purposes

| Fork | Remote URL | Replaces | Active branch |
|---|---|---|---|
| `se7enxweb/site-bundle` | `git@github-as-7x:se7enxweb/site-bundle.git` | `netgen/site-bundle` | `3.0.x` |
| `se7enxweb/oss` | `git@github-as-7x:se7enxweb/oss.git` | — (metapackage) | `master` |
| `se7enxweb/exponential-platform-nexus` | `git@github.com:se7enxweb/exponential-platform-nexus.git` | — (this project) | `1.2.0.x` |

---

Copyright © 1998–2026 7x (se7enx.com). All rights reserved unless otherwise noted.
Exponential Platform Nexus is Open Source software. See [LICENSE](../../LICENSE) and
[LICENSE-bul](../../LICENSE-bul) for the full licence texts.
