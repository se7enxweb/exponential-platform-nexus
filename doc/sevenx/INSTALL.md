# Exponential Platform Nexus v5 — 7x Installation & Operations Guide

> **Exponential Platform Nexus v5** is the 7x fork and customisation of the Netgen
> Media Site skeleton for Ibexa DXP v5 OSS. It runs on **Symfony 7.4 LTS** with
> **PHP 8.4+** (PHP 8.5 recommended) and includes a suite of 7x-maintained forks that
> resolve PHP 8.4, Twig 3.24, and Webpack Encore 5.x incompatibilities in upstream
> packages.
>
> This guide covers everything from first-time installation to day-to-day operations,
> the 7x forks in full technical detail, production deployment, and troubleshooting.
>
> **Read this guide in full before starting.** The 7x fork sections have a required
> ordering — misapplying them (e.g. editing vendor directly without going through
> Composer) leads to errors that reappear on `composer install`.

---

> **Console Command Prefix Convention**
>
> The kernel of this line (`se7enxweb/exponential-platform-dxp-core` v5.0.x) renames the commands it
> ships to the `exponential:` prefix, for example `exponential:install`, `exponential:reindex`,
> `exponential:urls:regenerate-aliases`, `exponential:content:cleanup-versions`, `exponential:copy-subtree`
> and `exponential:io:migrate-files`. **The old `ibexa:` names of these commands are not registered as
> aliases**: `php bin/console ibexa:reindex` ends with `Command "ibexa:reindex" is not defined.` The only alias the kernel
> defines is `exponential:debug:config` for `exponential:debug:config-resolver`.
>
> | Use | Not available on this line |
> |---|---|
> | `exponential:install`, `exponential:reindex`, … | `ibexa:install`, `ibexa:reindex`, … |
>
> Commands of the other Ibexa packages keep their `ibexa:` name (e.g. `ibexa:cron:run`,
> `ibexa:graphql:generate-schema`, `ibexa:system-info:dump`). `php bin/console list exponential` and
> `php bin/console list ibexa` show what your installation registers.

---

## Table of Contents

1. [Requirements](#1-requirements)
2. [Architecture Overview](#2-architecture-overview)
3. [7x Forks — What They Fix and Why They Are Required](#3-7x-forks--what-they-fix-and-why-they-are-required)
   - [3a. `se7enxweb/fieldtype-richtext`](#3a-se7enxwebfieldtype-richtext)
   - [3b. `se7enxweb/layouts-core`](#3b-se7enxweblayouts-core)
   - [3c. `se7enxweb/site-bundle` fix](#3c-se7enxwebsite-bundle-fix)
   - [3d. `config/bundles.php` — required manual additions](#3d-configbundlesphp--required-manual-additions)
   - [3e. Symfony Flex recipe hazard](#3e-symfony-flex-recipe-hazard)
   - [3f. `se7enxweb/exponential-platform-dxp` metapackage](#3f-se7enxwebexponential-platform-dxp-metapackage)
4. [First-Time Installation](#4-first-time-installation)
   - [4a. GitHub git clone (developers)](#4a-github-git-clone-developers)
5. [Environment Configuration (.env.local)](#5-environment-configuration-envlocal)
   - [Minimum required variables](#minimum-required-variables)
   - [MySQL / MariaDB](#mysql--mariadb)
   - [PostgreSQL](#postgresql-alternative-to-mysql)
   - [SQLite (zero-config — dev / testing)](#sqlite-zero-config--dev--testing)
   - [Search engine](#search-engine)
   - [HTTP cache](#http-cache)
   - [Application cache](#application-cache-backend)
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
10. [Admin UI Assets (Platform v5 Admin UI)](#10-admin-ui-assets-platform-v5-admin-ui)
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

- **PHP 8.4+** (PHP 8.5 strongly recommended — the server at `alpha.se7enx.com` runs PHP 8.5.5)
- Required extensions: `gd` or `imagick`, `curl`, `json`, `pdo_mysql` or `pdo_pgsql` or `pdo_sqlite`,
  `xsl`, `xml`, `intl`, `mbstring`, `opcache`, `ctype`, `iconv`
- For SQLite: `pdo_sqlite` + `sqlite3` PHP extensions (usually bundled with PHP;
  verify with `php -m | grep -i sqlite`)
- `memory_limit` ≥ 256M (512M recommended)
- `date.timezone` must be set in `php.ini`
- `max_execution_time` ≥ 120 (300 recommended for CLI operations)

> **PHP 8.4 minimum is non-negotiable** for this project. The `composer.json` declares
> `"php": ">=8.4"`. The 7x forks in this project exist specifically because upstream
> packages had regressions under PHP 8.4 that were not fixed in time.

### Web Server

- **Apache 2.4** with `mod_rewrite`, `mod_deflate`, `mod_headers`, `mod_expires` enabled; run in
  `event` or `worker` mode with PHP-FPM _or_
- **Nginx 1.18+** with PHP-FPM

The server at `alpha.se7enx.com` runs PHP-FPM under pool `alpha:psacln`. All
`php bin/console` commands run as root but the FPM worker runs as `alpha:psacln`.
The SQLite database file (`var/data_dev.db`) must be owned `alpha:psacln` with mode
`660` for FPM to write it.

### Node.js & Yarn

> **⚠ This project requires Node.js 22. Do NOT use Node.js 20 or earlier.**
>
> The `package.json` declares `"engines": {"node": "^22"}`. The `@ibexa/frontend-config`
> package and its webpack configurations are not compatible with Node.js 20. All `yarn`
> commands will fail or produce incorrect output unless Node.js 22 is active.

- [Node.js](https://nodejs.org/en/download/) **22** — managed via
  [nvm](https://github.com/nvm-sh/nvm) (strongly recommended)
- [Yarn](https://classic.yarnpkg.com/en/docs/install) **1.22.x** — activated via
  [corepack](https://github.com/nodejs/corepack) `enable` after `nvm use 22`

Installing nvm + Node.js 22:

```bash
# Universal installer — Linux, macOS, BSD, WSL
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.2/install.sh | bash
source ~/.nvm/nvm.sh       # or restart your shell
nvm install 22
nvm use 22
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
| PHP | 8.4 | 8.5 |
| Composer | 2.x | latest 2.x |
| Node.js | **22** | **22** (via nvm) |
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

Exponential Platform Nexus v5 is a **single-kernel Symfony 7.4 LTS application** with:

```
Browser Request
      │
      ▼
   Web Server (Apache / Nginx)
      │
      ▼
  public/index.php  ── Symfony Kernel (Platform v5 OSS — Symfony 7.4 LTS)
      │
      ├── URI: /adminui/**           → Platform v5 Admin UI (React)       ← siteaccess: adminui
      ├── URI: /api/ezp/v2/**        → REST API v2 (JWT-auth)             ← siteaccess: adminui
      ├── URI: /graphql              → GraphQL API                         ← siteaccess: adminui
      ├── URI: /nglayouts/**         → Netgen Layouts admin + app          ← siteaccess: adminui
      ├── URI: /bold_eng/**, /bold_ger/** → Bold Agency front end          ← siteaccess: bold_eng / bold_ger
      └── URI: /**                   → Platform v5 Twig/Symfony Front End  ← siteaccess: fh_eng (default)
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
│   ├── bundles.php                 Bundle registration (includes 7x manual additions)
│   ├── packages/                   Symfony/Ibexa/Netgen package config
│   ├── routes/                     Route inclusions (including netgen_layouts.yaml)
│   └── services.yaml               Application service definitions
├── src/
│   ├── Controller/                 Symfony controllers
│   ├── Entity/                     Doctrine entities
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
│   └── data_dev.db                 SQLite database (default for dev)
├── vendor/
│   ├── se7enxweb/fieldtype-richtext/   7x fork (replaces ibexa/fieldtype-richtext)
│   ├── se7enxweb/layouts-core/         7x fork (replaces netgen/layouts-core)
│   ├── se7enxweb/site-bundle/          7x fork with setContainer fix
│   └── ...
├── composer.json                   Declares se7enxweb/exponential-platform-dxp
├── package.json                    Declares Node.js 22 engine requirement
└── webpack.config.js               Webpack Encore config (ibexa + project builds)
```

### Siteaccesses

The siteaccesses are defined in `config/app/packages/ibexa_siteaccess.yaml` and matched by the first URI element
(`URIElement: 1` in `config/app/server/dev/ibexa_siteaccess.yaml` and `config/app/server/prod.yaml`); a request
without a siteaccess in its path goes to the default, `fh_eng`.

| Siteaccess | URL prefix | Groups | Purpose |
|---|---|---|---|
| `fh_eng` | `/fh_eng/` or `/` (default) | `frontend_group`, `fh_group` | Public front end (Fit & Healthy design, English) |
| `bold_eng` | `/bold_eng/` | `frontend_group`, `bold_group` | Public front end (Bold Agency design, English) |
| `bold_ger` | `/bold_ger/` | `frontend_group`, `bold_group` | Public front end (Bold Agency design, German) |
| `adminui` | `/adminui/` | `admin_group` | Platform v5 Admin UI (React) + REST API + GraphQL + Netgen Layouts |

The administration siteaccess's name comes from the parameter `ngsite.admin_siteaccess_name` (`adminui`).
This line has no `ngadminui` or `legacy_admin` siteaccess (there is no legacy kernel).

---

## 3. 7x Forks — What They Fix and Why They Are Required

This section is the canonical technical reference for every upstream incompatibility
fixed by 7x and the permanent fix applied. Read this before debugging any error.

### 3a. `se7enxweb/fieldtype-richtext`

- **Replaces**: `ibexa/fieldtype-richtext`
- **GitHub**: https://github.com/se7enxweb/fieldtype-richtext
- **Packagist**: https://packagist.org/packages/se7enxweb/fieldtype-richtext
- **Active branches**: `main`, `5.0.x`
- **Installed via**: `se7enxweb/exponential-platform-dxp` metapackage

#### Problem 1 — XSL stylesheet paths hardcoded to upstream vendor path

`ibexa/fieldtype-richtext` registers its XSL stylesheets via two Symfony service
configuration files:

**`src/bundle/Resources/config/default_settings.yaml`**
```yaml
# upstream (broken when using fork):
ibexa.field_type.richtext.resources:
    - vendor/ibexa/fieldtype-richtext/src/lib/RichText/Resources/stylesheets/...
```

**`src/bundle/Resources/config/fieldtype_services.yaml`**
```yaml
# upstream (broken when using fork):
arguments:
    $xslStylesheetPath: '%kernel.project_dir%/vendor/ibexa/fieldtype-richtext/...'
```

When the package is installed as `se7enxweb/fieldtype-richtext`, the path
`vendor/ibexa/fieldtype-richtext` does not exist. XSL transformations (used by the
RichText field type when rendering rich text content) fail with `file not found` errors.

**Fix applied in the fork:**
Both files are patched to use `vendor/se7enxweb/fieldtype-richtext/...` as the path.
This is committed to the `5.0.x` and `main` branches.

#### Problem 2 — Bundle not auto-registered by Symfony Flex

`IbexaFieldTypeRichTextBundle` is not automatically added to `config/bundles.php`
when using the `se7enxweb/fieldtype-richtext` fork via the Flex recipe system. It must
be added manually:

```php
// config/bundles.php
Ibexa\Bundle\FieldTypeRichText\IbexaFieldTypeRichTextBundle::class => ['all' => true],
```

See [Section 3d](#3d-configbundlesphp--required-manual-additions) for the complete
list of manually required bundle additions.

#### How the `replace` directive works in Composer

The fork's `composer.json` declares:
```json
"name": "se7enxweb/fieldtype-richtext",
"replace": {
    "ibexa/fieldtype-richtext": "self.version"
}
```

This tells Composer that `se7enxweb/fieldtype-richtext` is a drop-in replacement for
`ibexa/fieldtype-richtext`. Any package in the dependency tree that declares
`"require": {"ibexa/fieldtype-richtext": "^5.0"}` will have that requirement satisfied
by the fork without modifying any other package's `composer.json`.

---

### 3b. `se7enxweb/layouts-core`

- **Replaces**: `netgen/layouts-core`
- **GitHub**: https://github.com/se7enxweb/layouts-core
- **Packagist**: https://packagist.org/packages/se7enxweb/layouts-core
- **Active branches**: `master` (default), `main`, `2.0.x`
- **Tag**: `2.0.0-se7enx.1`
- **Installed via**: `se7enxweb/exponential-platform-dxp` metapackage

#### Problem — PHP 8.4 `private(set)` + Twig 3.24 `CoreExtension::getAttribute` incompatibility

This is the most technically involved issue fixed by the 7x forks. Understanding it fully
prevents re-introducing the bug.

**PHP 8.4 introduced asymmetric visibility (`private(set)`):**
```php
class Parameter {
    public private(set) string $name;
    public private(set) ParameterDefinition $parameterDefinition;
    public private(set) mixed $value;
    public private(set) bool $isEmpty;
    public private(set) ?object $valueObject;
}
```

`private(set)` means the property is **publicly readable** but **privately writable**.
This is valid PHP 8.4+ syntax.

**How Twig 3.24 resolves object attributes:**

When a Twig template accesses `parameter.isEmpty`, Twig's `CoreExtension::getAttribute()`
(and Ibexa Site API's custom `twigGetAttribute()`, which wraps it) proceeds through this
resolution order:

1. Check if `$parameter` is an array (no)
2. Check if `$parameter->isEmpty` is a public property — it does so using
   `ReflectionProperty::isInitialized($parameter)` 

**Here is where it breaks:** For a `private(set)` property, PHP's Reflection API returns
`false` from `isInitialized()` in Twig's context (the reflection check fails because
the property's set-visibility restriction confuses the read check). Twig therefore
concludes the property does not exist and moves on.

3. Try method resolution: look for `isEmpty()`, `getIsEmpty()`, `isIsEmpty()`, `hasIsEmpty()`
   as public methods.

Since the upstream `Parameter` class has **no public methods at all** (only
`private(set)` properties), every method lookup fails. Twig throws:

```
Twig\Error\RuntimeError: An exception has been thrown during the rendering of a template
("None of the chained routers were able to generate route: Route 'nglayouts_app' not found")
```

or more directly:

```
RuntimeException: Call to undefined method
Netgen\Layouts\Parameters\Parameter::isEmpty()
```

This error fires on every page that includes a Netgen Layouts zone that has parameters
(which is essentially any page using Netgen Layouts in a non-trivial way).

**The fix — explicit public getter shims in `lib/Parameters/Parameter.php`:**

```php
// se7enxweb/layouts-core — lib/Parameters/Parameter.php (added methods)

public function getName(): string
{
    return $this->name;
}

public function getParameterDefinition(): ParameterDefinition
{
    return $this->parameterDefinition;
}

public function getValue(): mixed
{
    return $this->value;
}

public function isEmpty(): bool
{
    return $this->isEmpty;
}

public function getValueObject(): ?object
{
    return $this->valueObject;
}
```

With `isEmpty()` as a public method, Twig's step 3 (method resolution) finds it and
the error disappears. The `private(set)` properties are still there — this fix is purely
additive and does not change any existing behaviour.

**Why not just fix the upstream?**

The `netgen/layouts-core` v2.0.0 release does not include these methods. Upstream has
not yet released a fix targeting this exact issue. The fork provides an immediate,
dependency-graph-safe solution that is installed automatically via Composer.

#### How the `replace` directive works in Composer

The fork's `composer.json` declares:
```json
"name": "se7enxweb/layouts-core",
"replace": {
    "netgen/layouts-core": "self.version"
}
```

Both `netgen/layouts-ibexa` and `netgen/layouts-standard` declare:
```json
"require": {
    "netgen/layouts-core": "~2.0.0"
}
```

Composer resolves the `replace` directive and uses `se7enxweb/layouts-core` to satisfy
both requirements. No changes are needed in any other package's `composer.json`.

---

### 3c. `se7enxweb/site-bundle` fix

- **GitHub**: https://github.com/se7enxweb/site-bundle
- **Affected branch**: `5.0.x`
- **Commit**: `b1a984d`
- **Affected file**: `bundle/Resources/config/services/controllers.yaml`

#### Problem — `ngsite.controller.base` missing `setContainer`

The base controller service for `se7enxweb/site-bundle` extends Symfony's
`ContainerAwareInterface`. In PHP 8.x with Symfony 7.4 strict DI, the container must
be injected via an explicit `setContainer` service call. Without it, any controller
extending `ngsite.controller.base` throws:

```
LogicException: The container was not set.
```

This error occurs on the first request to any Netgen Site front-end controller action.

**Fix applied:**

```yaml
# bundle/Resources/config/services/controllers.yaml
ngsite.controller.base:
    abstract: true
    calls:
        - [setContainer, ['@service_container']]
```

This is committed and pushed to the `5.0.x` branch. It is installed automatically when
`"se7enxweb/site-bundle": "5.0.x-dev"` is resolved by Composer.

---

### 3d. `config/bundles.php` — required manual additions

Symfony Flex does not auto-register all required bundles when the 7x forks are used.
The following lines must be present in `config/bundles.php`. They are already committed
to this project's repository — do not remove them.

```php
// Required for se7enxweb/fieldtype-richtext fork (not auto-registered by Flex):
Ibexa\Bundle\FieldTypeRichText\IbexaFieldTypeRichTextBundle::class => ['all' => true],

// Required — must be present for Twig UX components:
Symfony\UX\TwigComponent\Bundle\TwigComponentBundle::class => ['all' => true],

// Required — Netgen Layouts core bundles.
// These are registered by the netgen/layouts-core Flex recipe.
// If they disappear (see Section 3e), restore them from git.
Netgen\Bundle\LayoutsBundle\NetgenLayoutsBundle::class => ['all' => true],
Netgen\Bundle\LayoutsAdminBundle\NetgenLayoutsAdminBundle::class => ['all' => true],
```

---

### 3e. Symfony Flex recipe hazard

> **This is the single most common source of breakage when running `composer update`.**

When Composer transitions from `netgen/layouts-core` to `se7enxweb/layouts-core`
(or vice versa), Symfony Flex runs the `netgen/layouts-core` **unconfigure recipe**.
This recipe removes:

1. `Netgen\Bundle\LayoutsBundle\NetgenLayoutsBundle` from `config/bundles.php`
2. `Netgen\Bundle\LayoutsAdminBundle\NetgenLayoutsAdminBundle` from `config/bundles.php`
3. `config/routes/netgen_layouts.yaml` (deleted)

If this happens, the site returns HTTP 500 with:

```
Container extension "netgen_layouts" is not registered.
```

and the Netgen Layouts admin/app routes are missing.

**Recovery (after an accidental recipe unconfigure):**

```bash
cd /path/to/project

# Restore both files from git HEAD (last known good state):
git checkout HEAD -- config/bundles.php config/routes/netgen_layouts.yaml

# Clear cache and verify:
php bin/console cache:clear
curl -sk -o /dev/null -w "%{http_code}\n" https://your-site-domain/
```

**Prevention:**

After any `composer update` that touches `netgen/layouts-*` or `se7enxweb/layouts-*`
packages, always check:

```bash
git diff config/bundles.php config/routes/
```

If `NetgenLayoutsBundle`, `NetgenLayoutsAdminBundle`, or `config/routes/netgen_layouts.yaml`
are missing, restore them immediately before proceeding.

---

### 3f. `se7enxweb/exponential-platform-dxp` metapackage

- **GitHub**: https://github.com/se7enxweb/exponential-platform-dxp
- **Packagist**: https://packagist.org/packages/se7enxweb/exponential-platform-dxp
- **Branch**: `master`

This is the **central metapackage** that wires the entire Exponential Platform v5 DXP
dependency graph together. It:

- Requires all Ibexa DXP v5 OSS packages
- Requires `se7enxweb/fieldtype-richtext` (the 7x fork)
- Requires `se7enxweb/layouts-core` (the 7x fork)
- Requires all Symfony 7.4 LTS packages
- Requires all supporting Doctrine, Twig, and Symfony ecosystem packages

**You should not require the 7x forks directly in this project's `composer.json`.** The
metapackage handles all of that. If a fork needs to be updated:

1. Commit the fix to the fork repository
2. Update the fork's Packagist webhook (or wait for the auto-update)
3. Update the metapackage's `composer.json` if the version constraint needs changing
4. Run `composer update se7enxweb/exponential-platform-dxp` in the project

---

## 4. First-Time Installation

### 4a. GitHub git clone (developers)

```bash
git clone git@github.com:se7enxweb/exponential-platform-nexus.git
cd exponential-platform-nexus
git checkout 1.3.0.x
```

#### Step 1 — Install PHP dependencies

```bash
COMPOSER_ALLOW_SUPERUSER=1 composer install
```

This installs all packages including the 7x forks via `se7enxweb/exponential-platform-dxp`,
runs Symfony Flex recipes, and runs the `post-install-cmd` scripts (`cache:clear`,
`assets:install`, `ngsite:symlink:project`).

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

#### Step 5 — Build frontend assets (Node.js 22 required)

```bash
source ~/.nvm/nvm.sh && nvm use 22    # REQUIRED — do not skip this
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
| `https://127.0.0.1:8000/` | Platform v5 Symfony/Twig public site |
| `https://127.0.0.1:8000/adminui/` | **Platform v5 Admin UI** (React) |
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

SQLite is the **default database for this project**. The `.db` file is created
automatically when you run `exponential:install`. No database server is required.

```bash
# In .env.local — this is the default already set in .env:
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"

# Required for SQLite — the doctrine transport requires a second DB connection
# that SQLite cannot support; sync:// processes messages synchronously instead:
MESSENGER_TRANSPORT_DSN=sync://
```

**SQLite file permissions (critical):**

The install command typically runs as your shell user (or root). The web server /
PHP-FPM pool must have write access to the `.db` file:

```bash
# On alpha.se7enx.com (PHP-FPM pool: alpha:psacln):
chmod 660 var/data_dev.db
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
SEARCH_ENGINE=legacy       # default — uses the Ibexa legacy search engine
# SEARCH_ENGINE=solr       # use Solr (see Section 19)
```

### HTTP cache

```bash
HTTPCACHE_PURGE_TYPE=local              # or "varnish" when using Varnish
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

SQLite requires no prior setup. The installer creates the file automatically.

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
1. Creates `var/data_dev.db` (or `var/data_prod.db` for `--env=prod`)
2. Imports the Exponential Media seed content (content classes, content objects, users, roles)
3. Creates all Doctrine ORM tables

Default credentials after install: **`admin` / `publish`**

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

    ssl_certificate     /etc/ssl/certs/your-site.crt;
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

# Platform v5 public var directory (thumbnails, generated content)
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

The project uses Webpack Encore + Yarn. **Node.js 22 is required — do not use Node 20.**

```bash
source ~/.nvm/nvm.sh && nvm use 22    # REQUIRED
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

## 10. Admin UI Assets (Platform v5 Admin UI)

The Platform v5 Admin UI assets (React components, SCSS, icons) are built separately from
the site frontend. They are not rebuilt automatically on `composer install`.

### Prerequisites

The `var/encore/` directory must be populated by `assets:install` before any `ibexa:*`
build can run:

```bash
php bin/console assets:install --symlink --relative public
```

This publishes bundle `public/` directories to `public/bundles/` and writes the
`var/encore/ibexa.config.js`, `var/encore/ibexa.config.setup.js`, and
`var/encore/ibexa.config.manager.js` files that tell webpack where each bundle's entry
points are.

### Build Admin UI assets — production

```bash
nvm use 22 && yarn ibexa:build
```

### Build Admin UI assets — development (with source maps)

```bash
nvm use 22 && yarn ibexa:dev
```

### Watch mode

```bash
nvm use 22 && yarn ibexa:watch
```

> **All `ibexa:*` scripts** route through the project's `webpack.config.js` via
> `--config-name ibexa`. This is what ensures the `@ibexa-admin-ui` webpack alias
> resolves to `vendor/se7enxweb/fieldtype-richtext` (the fork) and not to the upstream
> path. If you bypass this and call webpack directly, the alias will be wrong.

### Dump JS translation assets (required for Admin UI i18n)

```bash
php bin/console bazinga:js-translation:dump public/assets --merge-domains
```

### What changes require an Admin UI asset rebuild

| Change | Rebuild needed |
|---|---|
| `composer update` pulled a new `se7enxweb/fieldtype-richtext` | Yes — `yarn ibexa:build` |
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

Back up `config/jwt/private.pem` and `config/jwt/public.pem` securely. If lost, all
existing tokens are invalidated and clients must re-authenticate.

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
php bin/console cache:clear                # current APP_ENV
php bin/console cache:clear --env=prod     # production cache
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

# 3. CRITICAL: Check if Flex removed Layouts bundles/routes
git diff config/bundles.php config/routes/
# If NetgenLayoutsBundle, NetgenLayoutsAdminBundle, or netgen_layouts.yaml are missing:
git checkout HEAD -- config/bundles.php config/routes/netgen_layouts.yaml

# 4. Run Doctrine migrations
php bin/console doctrine:migration:migrate --allow-no-migration --env=prod

# 5. Publish bundle public assets
php bin/console assets:install --symlink --relative public --env=prod

# 6. Rebuild Platform v5 Admin UI assets (if admin-ui bundle updated)
source ~/.nvm/nvm.sh && nvm use 22 && yarn ibexa:build

# 7. Rebuild frontend site assets (if theme/JS/CSS changed)
yarn build:prod

# 8. Dump JS translations
php bin/console bazinga:js-translation:dump public/assets --merge-domains --env=prod

# 9. Clear & warm up caches
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Fix cache permissions if running as root but FPM is a different user:
chmod -R a+rX var/cache/

# 10. Reindex search (if content model changed)
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
COMPOSER_ALLOW_SUPERUSER=1 composer update se7enxweb/layouts-core

# After update, ALWAYS check for accidentally removed Layouts bundles:
git diff config/bundles.php config/routes/
# Then run:
php bin/console doctrine:migration:migrate --allow-no-migration
php bin/console cache:clear
```

> 💾 **Git Save Point — After composer update**
> ```bash
> git add composer.lock && git commit -m "chore(deps): composer update $(date +%Y-%m-%d)"
> ```

### Updating the 7x forks

The forks are versioned packages on Packagist. To pick up a new fix:

1. Commit the fix to the fork repository (e.g. `se7enxweb/layouts-core` on GitHub)
2. Packagist auto-updates within minutes (webhook on push)
3. Run `composer update se7enxweb/layouts-core` in the project
4. Verify the fix is installed: `grep -r "isEmpty" vendor/se7enxweb/layouts-core/lib/Parameters/Parameter.php`
5. Clear cache and test: `php bin/console cache:clear && curl ...`
6. Commit `composer.lock`

---

## 18. Cron Jobs

Add to crontab (`crontab -e -u www-data` or for the FPM user):

```bash
# Platform v5 cron runner (every 5 minutes)
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
3. Load the Platform v5 Varnish VCL from `doc/varnish/` (if present)
4. Clear cache:
   ```bash
   php bin/console cache:clear
   php bin/console fos:httpcache:invalidate:path / --all
   ```

---

## 21. Troubleshooting

### HTTP 500 — "Container extension `netgen_layouts` is not registered"

The Symfony Flex recipe unconfigure removed `NetgenLayoutsBundle` from `bundles.php`.

```bash
git checkout HEAD -- config/bundles.php config/routes/netgen_layouts.yaml
php bin/console cache:clear
```

### HTTP 500 — "Route 'nglayouts_app' not found"

`config/routes/netgen_layouts.yaml` was deleted by the unconfigure recipe.

```bash
git checkout HEAD -- config/routes/netgen_layouts.yaml
php bin/console cache:clear
```

Or manually restore the file:

```yaml
# config/routes/netgen_layouts.yaml
netgen_layouts:
    resource: "@NetgenLayoutsBundle/Resources/config/routing.yaml"
    prefix: "%netgen_layouts.route_prefix%"
```

### HTTP 500 — "Call to undefined method Parameter::isEmpty()"

The `se7enxweb/layouts-core` fork is not installed, or a `composer install` restored
the upstream `netgen/layouts-core` without the getter shims.

```bash
# Check which layouts-core package is installed:
composer show netgen/layouts-core
composer show se7enxweb/layouts-core

# If netgen/layouts-core is installed instead of se7enxweb/layouts-core:
COMPOSER_ALLOW_SUPERUSER=1 composer update se7enxweb/exponential-platform-dxp se7enxweb/layouts-core --no-cache

# Verify the shims are present:
grep -n "public function isEmpty\|public function getValue\|public function getName" \
    vendor/se7enxweb/layouts-core/lib/Parameters/Parameter.php

# Clear cache:
php bin/console cache:clear
```

If the fix is in vendor but still not working, the cache is stale:

```bash
rm -rf var/cache/dev/*
php bin/console cache:warmup
```

### HTTP 500 — XSL stylesheet not found / RichText rendering fails

The `ibexa/fieldtype-richtext` upstream package is installed instead of
`se7enxweb/fieldtype-richtext`, or the paths in the fork have reverted.

```bash
# Check which richtext package is installed:
composer show ibexa/fieldtype-richtext
composer show se7enxweb/fieldtype-richtext

# Verify fork paths:
grep -n "se7enxweb\|ibexa/fieldtype" \
    vendor/se7enxweb/fieldtype-richtext/src/bundle/Resources/config/default_settings.yaml

# If upstream is installed, update:
COMPOSER_ALLOW_SUPERUSER=1 composer update se7enxweb/fieldtype-richtext --no-cache
php bin/console cache:clear
```

### Admin UI not loading — `IbexaFieldTypeRichTextBundle` not registered

```php
// Verify in config/bundles.php:
Ibexa\Bundle\FieldTypeRichText\IbexaFieldTypeRichTextBundle::class => ['all' => true],
```

If missing, add it. Then:

```bash
php bin/console cache:clear
```

### `yarn ibexa:build` fails with "Module not found" or SASS errors

Most commonly caused by:
1. Wrong Node.js version — must be 22
2. `var/encore/` not populated (run `assets:install` first)
3. Stale `node_modules/`

```bash
# Check Node.js version:
node --version    # must be v22.x.x

# Activate Node 22 if needed:
nvm use 22

# Ensure var/encore/ is populated:
php bin/console assets:install --symlink --relative public

# If still failing, reinstall node_modules:
rm -rf node_modules yarn.lock
yarn install
yarn ibexa:build
```

### `yarn ibexa:build` fails — "Cannot find module '@ibexa/frontend-config'"

```bash
yarn install
# then retry:
yarn ibexa:build
```

### SQLite — `attempt to write a readonly database`

The FPM user cannot write `var/data_dev.db`. Fix:

```bash
# alpha.se7enx.com:
chmod 660 var/data_dev.db
chown alpha:psacln var/data_dev.db

# Generic:
chmod 660 var/data_dev.db
chown $USER:www-data var/data_dev.db
```

### SQLite — `no such table: ibexa_section`

The database was not initialised. Run:

```bash
php bin/console exponential:install exponential-media --no-interaction
# Then fix permissions:
chmod 660 var/data_dev.db && chown alpha:psacln var/data_dev.db
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

---

## 22. Database Conversion

This section covers converting an existing, running Exponential Platform Nexus v5
application from one database engine to another using **free and open-source tools
only**.

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
> cp var/data_dev.db var/data_dev.db.bak
> # Also backup .env.local
> cp .env.local .env.local.bak
> ```

### Tool inventory

All tools are free and open-source.

#### `mysqldump` / `mysql` CLI

Bundled with every MySQL and MariaDB server package.
Download: [dev.mysql.com/downloads/mysql](https://dev.mysql.com/downloads/mysql/)

| OS | Install |
|---|---|
| Debian / Ubuntu / Mint | `apt install default-mysql-client` |
| RHEL / AlmaLinux / Rocky | `dnf install mysql` |
| Fedora | `dnf install community-mysql` |
| Arch / Manjaro | `pacman -S mysql-clients` |
| FreeBSD | `pkg install mysql80-client` |
| macOS (Homebrew) | `brew install mysql-client` |

#### `pg_dump` / `psql`

Bundled with PostgreSQL server packages.
Download: [postgresql.org/download](https://www.postgresql.org/download/)

| OS | Install |
|---|---|
| Debian / Ubuntu / Mint | `apt install postgresql-client` |
| RHEL / AlmaLinux / Rocky | `dnf install postgresql` |
| Arch / Manjaro | `pacman -S postgresql-libs` |
| FreeBSD | `pkg install postgresql16-client` |
| macOS (Homebrew) | `brew install libpq` |

#### `sqlite3` CLI

| OS | Install |
|---|---|
| Debian / Ubuntu / Mint | `apt install sqlite3` |
| RHEL / AlmaLinux / Rocky | `dnf install sqlite` |
| Arch / Manjaro | `pacman -S sqlite` |
| FreeBSD | `pkg install sqlite3` |
| macOS | pre-installed on all versions |

#### pgloader

Docs: [pgloader.io](https://pgloader.io/) ·
Source: [github.com/dimitri/pgloader](https://github.com/dimitri/pgloader) ·
Licence: PostgreSQL (BSD-like)

| OS | Install |
|---|---|
| Debian / Ubuntu / Mint | `apt install pgloader` |
| Fedora | `dnf install pgloader` |
| Arch / Manjaro | `yay -S pgloader` |
| FreeBSD | `pkg install pgloader` |
| macOS (Homebrew) | `brew install pgloader` |
| Docker (any OS) | `docker run --rm -it dimitri/pgloader:latest pgloader <args>` |

#### mysql2sqlite

Download: [github.com/dumblob/mysql2sqlite](https://github.com/dumblob/mysql2sqlite) ·
Licence: MIT · single shell script, no compiled dependencies.

```bash
curl -LO https://raw.githubusercontent.com/dumblob/mysql2sqlite/master/mysql2sqlite
chmod +x mysql2sqlite
```

#### sqlite3-to-mysql

Download: [github.com/techouse/sqlite3-to-mysql](https://github.com/techouse/sqlite3-to-mysql) ·
Licence: MIT · Python package, requires Python 3.8+.

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

### 22c. SQLite → PostgreSQL

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

### 22d. MySQL / MariaDB → PostgreSQL

Use [pgloader](https://pgloader.io/) — its primary, most mature use-case:

```bash
psql -U postgres -c "CREATE DATABASE exponential ENCODING 'UTF8';"

cat > /tmp/mysql_to_pg.load <<'EOF'
LOAD DATABASE
  FROM     mysql://db_user:db_pass@127.0.0.1/source_db
  INTO     postgresql://pg_user:pg_pass@127.0.0.1/exponential

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

For the reverse direction (Oracle → open-source DB), `ora2pg` handles Oracle →
PostgreSQL; from PostgreSQL you can reach any other engine via the sections above.

```bash
# Install ora2pg (Perl, GPL v3)
# Debian / Ubuntu:
apt install ora2pg
# Or from source:
cpan DBD::Oracle
cpan ora2pg

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
chmod 660 var/data_dev.db
chown alpha:psacln var/data_dev.db   # alpha.se7enx.com; replace with your FPM user
```

#### Common post-conversion issues

| Symptom | Cause | Fix |
|---|---|---|
| `SQLSTATE[42S02]: Base table not found` | Table not migrated | Run `doctrine:schema:validate` and check pgloader/mysql2sqlite log for errors |
| Binary/blob content garbled | Charset mismatch during export | Re-export with `--default-character-set=utf8mb4` (mysqldump) or `CLIENT_ENCODING=UTF8` (psql) |
| Serialisation failure (PostgreSQL) | Concurrent access during import | Import with `APP_ENV=dev` and no web traffic; use a maintenance window |
| Image variation 404s | `ezcontentobject_attribute` row count mismatch | Verify row counts between source and target; re-run data transfer for that table |
| `SQLite attempt to write a readonly database` | Web server user cannot write the `.db` file | `chmod 660 var/data_*.db && chown $USER:www-data var/data_*.db` |

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

### 23.3 Platform v5 — `exponential:` Commands

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

# SiteAccess config resolver
php bin/console exponential:debug:config-resolver languages --siteaccess=fh_eng
php bin/console exponential:debug:config-resolver http_cache.purge_servers

# Content utilities
php bin/console exponential:content:cleanup-versions              # prune old versions
php bin/console exponential:urls:regenerate-aliases               # rebuild URL aliases
php bin/console exponential:copy-subtree <src-id> <dst-id>        # copy a subtree

# User management
php bin/console exponential:user:expire-password --force           # expire all passwords
php bin/console exponential:user:validate-password-hashes          # audit hash algos

# Full config dump
php bin/console debug:config ibexa                                 # dump Ibexa config
```

### 23.4 Frontend / Asset Build (Yarn / Webpack Encore)

```bash
# Node version management — REQUIRED FIRST
source ~/.nvm/nvm.sh && nvm use 22     # activate Node.js 22
corepack enable                         # activates Yarn 1.22.x

# Dependencies
yarn install                            # install / sync all Node dependencies
yarn upgrade                            # upgrade within semver constraints
yarn add <package>                      # add a new dependency
yarn remove <package>                   # remove a dependency

# Site asset builds
yarn build:dev                          # build with source maps (development)
yarn build:prod                         # build minified (production)
yarn watch                              # watch mode — auto-rebuild on change
yarn start                              # webpack dev server

# Admin UI asset builds
yarn ibexa:dev                          # build Platform v5 Admin UI — dev mode
yarn ibexa:build                        # build Platform v5 Admin UI — production
yarn ibexa:watch                        # watch Admin UI assets

# Code quality
yarn format:js                          # format JS with Prettier
yarn linter:js                          # lint JS with ESLint
```

### 23.5 Composer Maintenance

```bash
# Install / update
COMPOSER_ALLOW_SUPERUSER=1 composer install                         # install from lock file
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev -o             # production
COMPOSER_ALLOW_SUPERUSER=1 composer update                          # update all within constraints
COMPOSER_ALLOW_SUPERUSER=1 composer update se7enxweb/layouts-core   # update one package
COMPOSER_ALLOW_SUPERUSER=1 composer update --dry-run                # preview

# Autoloader
composer dump-autoload                   # regenerate autoloader
composer dump-autoload -o                # optimised (production)

# Info / Audit
composer show                            # list all installed packages
composer show se7enxweb/layouts-core     # detail one package
composer outdated                        # list outdated packages
composer audit                           # check for security advisories
composer validate                        # validate composer.json / composer.lock
```

### 23.6 Symfony CLI

```bash
symfony server:start                     # start HTTPS dev server (https://127.0.0.1:8000)
symfony server:start -d                  # start in background daemon mode
symfony server:stop                      # stop background server
symfony server:log                       # tail server access/error log
symfony server:status                    # show server status + URL

symfony check:requirements               # verify PHP + extension requirements
symfony check:security                   # audit composer.lock for known CVEs
symfony local:php:list                   # list PHP versions available via Symfony CLI
```

### 23.7 Git Workflow

```bash
# Branching
git checkout -b feature/my-feature       # new feature branch
git checkout 1.3.0.x                     # switch to the active branch

# Save Points
git add -A && git commit -m "chore: <description>"
git stash                                # save uncommitted work
git stash pop                            # restore stashed work

# Tags (deploy markers)
git tag -a "deploy-$(date +%Y%m%d-%H%M)" -m "deploy $(date)"
git push origin --tags

# Inspection
git log --oneline -20                    # last 20 commits
git diff HEAD                            # uncommitted changes
git status                               # working tree status
git diff config/bundles.php config/routes/   # check for Flex recipe damage after composer update
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
git clone git@github-as-7x:se7enxweb/layouts-core.git

# Add a remote for an existing clone:
git remote add se7enxweb git@github-as-7x:se7enxweb/layouts-core.git

# Push to the se7enxweb remote:
git push se7enxweb se7enxweb-2.0.x:master
```

### Fork repositories and their purposes

| Fork | Remote URL | Replaces | Active branch |
|---|---|---|---|
| `se7enxweb/fieldtype-richtext` | `git@github-as-7x:se7enxweb/fieldtype-richtext.git` | `ibexa/fieldtype-richtext` | `5.0.x`, `main` |
| `se7enxweb/layouts-core` | `git@github-as-7x:se7enxweb/layouts-core.git` | `netgen/layouts-core` | `master`, `main`, `2.0.x` |
| `se7enxweb/site-bundle` | `git@github-as-7x:se7enxweb/site-bundle.git` | upstream site-bundle | `5.0.x` |
| `se7enxweb/exponential-platform-dxp` | `git@github-as-7x:se7enxweb/exponential-platform-dxp.git` | — (metapackage) | `master` |
| `se7enxweb/exponential-platform-nexus` | `git@github.com:se7enxweb/exponential-platform-nexus.git` | — (this project) | `1.3.0.x` |

---

Copyright © 1998–2026 7x (se7enx.com). All rights reserved unless otherwise noted.
Exponential Platform Nexus is Open Source software. See [LICENSE](../../LICENSE) and
[LICENSE-bul](../../LICENSE-bul) for the full licence texts.
