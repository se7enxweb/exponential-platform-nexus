# 8. Configuration

An installed Exponential Platform Nexus is a Symfony application, and almost everything that makes it *this* site
rather than another lives in configuration: which siteaccesses answer which addresses, which languages each one
shows, where the content tree of each site starts, how images are scaled, which search engine the repository uses,
how Netgen Layouts and the Site API render pages, and, on the lines that carry it, which settings the Exponential
Platform Legacy kernel receives through the legacy bridge. This chapter walks through that configuration line by
line. It starts with where the files are and how an environment is chosen, covers environment variables and secrets,
then siteaccesses, repositories, languages, the Site API, Layouts, the site bundle parameters, image variations and
the legacy bridge, and ends with how a change is applied and checked.

[Previous: 7. Databases](07-databases.md) · [Next: 9. Front end and themes](09-frontend-and-themes.md) ·
[Contents](README.md)

---

## Contents of this chapter

1. [Where the configuration lives](#81-where-the-configuration-lives)
   1. [The four lines at a glance](#811-the-four-lines-at-a-glance)
   2. [1.0.0.x: app/config and parameters.yml](#812-100x-appconfig-and-parametersyml)
   3. [1.1.0.x, 1.2.0.x and 1.3.0.x: config/ and config/app/](#813-110x-120x-and-130x-config-and-configapp)
   4. [The YAML root key per line](#814-the-yaml-root-key-per-line)
2. [Environments](#82-environments)
   1. [Symfony environment and debug mode](#821-symfony-environment-and-debug-mode)
   2. [The server environment](#822-the-server-environment)
3. [Environment variables and .env files](#83-environment-variables-and-env-files)
   1. [The .env cascade (1.1.0.x and later)](#831-the-env-cascade-110x-and-later)
   2. [Variable reference](#832-variable-reference)
   3. [A production .env.local, worked example](#833-a-production-envlocal-worked-example)
   4. [1.0.0.x: parameters and environment variables](#834-100x-parameters-and-environment-variables)
4. [Secrets](#84-secrets)
5. [Siteaccesses](#85-siteaccesses)
   1. [Lists, groups and the scope order](#851-lists-groups-and-the-scope-order)
   2. [The siteaccesses each line ships](#852-the-siteaccesses-each-line-ships)
   3. [Matchers: how a request finds its siteaccess](#853-matchers-how-a-request-finds-its-siteaccess)
   4. [Designs and the theme fallback](#854-designs-and-the-theme-fallback)
   5. [Adding a host name for production](#855-adding-a-host-name-for-production)
6. [Repositories, search engine and cache pool](#86-repositories-search-engine-and-cache-pool)
7. [Languages and translations](#87-languages-and-translations)
8. [Netgen Site API](#88-netgen-site-api)
9. [Netgen Layouts and the Content Browser](#89-netgen-layouts-and-the-content-browser)
10. [Site bundle parameters (ngsite)](#810-site-bundle-parameters-ngsite)
11. [Image variations](#811-image-variations)
12. [Legacy bridge settings](#812-legacy-bridge-settings)
13. [Applying and checking a change](#813-applying-and-checking-a-change)
14. [The reference installation](#814-the-reference-installation)
15. [Checklist](#815-checklist)
16. [References](#816-references)

Every command in this chapter is run from the installation root, the directory that holds `composer.json`. The
console is `bin/console` on every line.

---

## 8.1 Where the configuration lives

### 8.1.1 The four lines at a glance

| | 1.0.0.x | 1.1.0.x | 1.2.0.x | 1.3.0.x |
|---|---|---|---|---|
| Platform underneath | eZ Platform 2.5 kernel (upstream) | eZ Platform 3.3 | Ibexa OSS 4.6 | Ibexa OSS v5 |
| Symfony | 3.4 | 5.4 | 5.4 | 7.4 |
| Kernel class | `AppKernel` (`app/AppKernel.php`) | `App\Kernel` (`src/Kernel.php`) | `App\Kernel` | `App\Kernel` |
| Front controller | `web/app.php` | `public/index.php` | `public/index.php` | `public/index.php` |
| Configuration directory | `app/config/` | `config/` | `config/` | `config/` |
| Project configuration | `app/config/*.yml`, `src/AppBundle/Resources/config/` | `config/app/` | `config/app/` | `config/app/` |
| Environment selected by | `SYMFONY_ENV` | `APP_ENV` | `APP_ENV` | `APP_ENV` |
| Per-host settings | `parameters.yml` | `.env.local` | `.env.local` | `.env.local` |
| Server environment files | `app/config/server/<name>.yml` | `config/app/server/<name>.yaml` | the same | the same |
| Legacy bridge | yes | yes | yes | no |

The `master` branch currently carries the 1.0.0.x layout: its `app/AppKernel.php` loads `app/config/` exactly like
1.0.0.x, and its `composer.json` requires the same Symfony 3.4 and eZ Platform 2.5 packages.

### 8.1.2 1.0.0.x: app/config and parameters.yml

`AppKernel::registerContainerConfiguration()` loads one file, `app/config/<environment>/config.yml`, and every other
file is reached from there through `imports`:

```
app/config/prod/config.yml
├── ../config.yml
│   ├── default_parameters.yml          defaults that read environment variables
│   ├── parameters.yml                  this host's values (created from parameters.yml.dist)
│   ├── security.yml
│   ├── cache_pool/cache.tagaware.filesystem.yml, cache_pool/cache.apcu.yml
│   ├── env/generic.php, env/platformsh.php
│   └── services.yml
├── ../sentry.yml
└── ezplatform.yml  ->  ../ezplatform.yml
    ├── ngadminui.yml
    ├── ezplatform_siteaccess_defaults.yml
    ├── ezplatform_siteaccess.yml
    ├── @NetgenAdminUIBundle/Resources/config/ezplatform.yml
    └── @AppBundle/Resources/config/ezplatform.yml
        └── parameters.yml, templates.yml, image.yml, opengraph.yml, legacy.yml,
            info_collection.yml, browser.yml        (all in src/AppBundle/Resources/config/)
```

`app/config/dev/config.yml` imports `../config.yml`, `ezplatform.yml` and `security.yml` and adds the profiler, a
development Encore build directory and development logging.

After the container is built, `AppKernel::buildContainer()` loads one more file:
`app/config/server/<server_environment>.yml`, where `server_environment` is a parameter (see
[8.2.2](#822-the-server-environment)).

The tree also contains a `config/` directory with `config/packages/` and `config/app/` files, a `src/Kernel.php` and
a `public/index.php`. They are not read by `web/app.php`, `bin/console` or `AppKernel`, which only look in
`app/config/`. Treat them as material carried over from the later layout, not as active configuration of a 1.0.0.x
site.

Several files in `app/config/` have siblings ending in `.cjw.ref0`, `.cjw.ref1` or `.current`. They are reference
copies and are not imported by anything.

### 8.1.3 1.1.0.x, 1.2.0.x and 1.3.0.x: config/ and config/app/

These lines use Symfony's MicroKernel. `src/Kernel.php` first loads the standard Symfony locations, then the
project's own directory:

| Order | What | Purpose |
|---|---|---|
| 1 | `config/packages/*.yaml`, `config/packages/<env>/*.yaml` | one file per bundle, as Symfony Flex recipes create them |
| 2 | `config/services.yaml` (1.3.0.x) | Symfony services |
| 3 | `config/app/packages/*.yaml` | the project's per-bundle configuration: siteaccesses, images, templates, Content Browser |
| 4 | `config/app/services.yaml`, `config/app/services/**/*.yaml` | the project's services |
| 5 | `config/app/app.yaml` | project parameters |
| 6 | `config/app/app_legacy.yaml` (1.2.0.x; 1.1.0.x since `v1.1.0.8`) | ImageMagick settings for the legacy kernel |
| 7 | `config/app/server/<SERVER_ENVIRONMENT>.yaml` | per-server values: location IDs, domains, matchers |
| 8 | `config/app/prepends/<extension>/*.yaml` | prepended to that extension's configuration by `App\DependencyInjection\AppExtension` |
| routes | `config/routes/*.yaml`, `config/app/routes/*.yaml`, `config/app/routes.yaml` | routing |

The prepends deserve a word. `AppExtension::prepend()` walks `config/app/prepends/`; each sub-directory is the name
of a bundle extension and every file in it is prepended to that extension's configuration. The project ships two:
`prepends/ibexa/` (content views and component views, including the Site API settings) and
`prepends/netgen_layouts/` (block types, block definitions, block views and item views). On 1.1.0.x the directory is
still called `ibexa/`, and the extension maps it to the `ezpublish` extension through a
`PREPEND_EXTENSION_ALIASES` constant; on 1.2.0.x and 1.3.0.x the directory name is used as is. Because prepended
configuration is merged *before* the files in `config/`, a value set in `config/app/packages/` or
`config/packages/` wins over the same value in a prepend.

The kernel validates `SERVER_ENVIRONMENT` against `^\w+$` and stops with an exception if it contains anything else.

### 8.1.4 The YAML root key per line

The repository, siteaccess and system settings sit under one root key whose name changed with the upstream product.
Copying a snippet from documentation written for another version is the most common configuration mistake, so check
the key first:

| Setting | 1.0.0.x | 1.1.0.x | 1.2.0.x | 1.3.0.x |
|---|---|---|---|---|
| Repository, siteaccess, `system` | `ezpublish:` | `ezpublish:` | `ibexa:` | `ibexa:` |
| Design engine | `ezdesign:` | `ezdesign:` | `ibexa_design_engine:` | `ibexa_design_engine:` |
| Template namespace for the design | `@ezdesign` | `@ezdesign` | `@ibexadesign` | `@ibexadesign` |
| Layouts and Site API bridge | `netgen_layouts_ez_platform_site_api:` | `netgen_layouts_ez_platform_site_api:` | `netgen_layouts_ibexa_site_api:` | `netgen_layouts_ibexa_site_api:` |
| Site API bundle class | `NetgenEzPlatformSiteApiBundle` | `NetgenEzPlatformSiteApiBundle` | `NetgenIbexaSiteApiBundle` | `NetgenIbexaSiteApiBundle` |
| Site API settings | `netgen_ez_platform_site_api:` (and `ng_site_api` in `system`) | `ng_site_api` in `ezpublish.system` | `ng_site_api` in `ibexa.system` | `ng_site_api` in `ibexa.system` |
| Legacy bridge | `ez_publish_legacy:`, `netgen_site_legacy:` | the same | the same | none |
| Solr | `ez_search_engine_solr:` | `ez_search_engine_solr:` | `ibexa_solr:` | `ibexa_solr:` |

`netgen_layouts:`, `netgen_content_browser:`, `netgen_tags:`, `netgen_information_collection:`,
`netgen_open_graph:` and `liip_imagine:` keep their names on every line.

---

## 8.2 Environments

### 8.2.1 Symfony environment and debug mode

Symfony runs in one *environment* (`dev`, `prod`, `test`, and `behat` on 1.0.0.x) and with debug on or off. The
environment decides which configuration files are added and whether the container is rebuilt when a file changes.

**1.1.0.x and later.** `public/index.php` uses the Symfony Runtime component, which reads `APP_ENV` and `APP_DEBUG`
from the real environment or from the `.env` files ([8.3](#83-environment-variables-and-env-files)). `APP_DEBUG`
defaults to on in `dev` and off otherwise. The committed `.env` sets `APP_ENV=dev`; a production host sets
`APP_ENV=prod` in `.env.local` or in the server's environment.

On these lines `APP_HTTP_CACHE` decides whether the Symfony reverse proxy (`AppCache`) runs in front of the kernel.
Since 5 October 2026 (the branch heads and the releases `v1.1.0.8`, `v1.2.0.1` and `1.3.0.6`) `public/index.php` reads it (true:
on; unset, empty or `0`: off); the releases `v1.1.0.7`, `v1.2.0.0` and `1.3.0.5` and older ignore it and never run the proxy
([chapter 10.4](10-operations.md#104-http-cache-and-purging)).

**1.0.0.x.** `web/app.php` reads `SYMFONY_ENV` (default `prod`), `SYMFONY_DEBUG` (default on in `dev`) and
`SYMFONY_HTTP_CACHE` (default: the Symfony reverse proxy `AppCache` is used outside `dev`). If a file `.env.php`
exists in the installation root, `web/app.php` and `bin/console` include it before the environment is read, so it can
`putenv()` values:

```php
<?php
// .env.php in the project root, not committed
putenv('SYMFONY_ENV=prod');
putenv('SYMFONY_SECRET=' . 'a-long-random-value');
```

Up to the releases `v2.5.0.6` and `1.0.0.10` `web/app.php` also switched to `dev` for any request whose host name
contains `dev.`. The branches removed that on 5 October 2026 (commits `dddc937de` and `4598c1942`, released in
`v2.5.0.7` and `1.0.0.11`); see
[chapter 14.4](14-security-hardening.md#144-debug-mode-and-the-environment) if your installation still has it.

`bin/console` on 1.0.0.x uses `--env`, else `SYMFONY_ENV`, else **`dev`**, so a production host must pass
`--env=prod` or export `SYMFONY_ENV=prod` for console commands; otherwise the console works against the `dev`
container while the web runs `prod`. The symptom is a `cache:clear` that "does nothing": it cleared
`var/cache/dev/`, and the site reads `var/cache/prod/`.

### 8.2.2 The server environment

Independent of the Symfony environment, the project chooses a *server environment*: a small set of files with the
values that differ between one server and the next, such as the location ID of each site's root, the site domain,
the contact form addresses and the siteaccess matchers.

| Line | Chosen by | Files | Shipped |
|---|---|---|---|
| 1.0.0.x | parameter `server_environment` in `parameters.yml` (default `dev` in `parameters.yml.dist`) | `app/config/server/<name>.yml` and what it imports | `dev` |
| 1.1.0.x | `SERVER_ENVIRONMENT` | `config/app/server/<name>.yaml` | `dev`, `prod` |
| 1.2.0.x | `SERVER_ENVIRONMENT` | `config/app/server/<name>.yaml` | `dev`; `prod` since `v1.2.0.1` |
| 1.3.0.x | `SERVER_ENVIRONMENT` | `config/app/server/<name>.yaml` | `dev`; `prod` since `1.3.0.6` |

The committed `.env` of 1.1.0.x, 1.2.0.x and 1.3.0.x sets `SERVER_ENVIRONMENT=dev`. `src/Kernel.php` imports
`config/app/server/<SERVER_ENVIRONMENT>.yaml` on every boot, so the name must have a file.

**1.2.0.x and 1.3.0.x, since `v1.2.0.1` and `1.3.0.6`.** Since 5 October 2026 (commits `cb7e181b8` and `88c4a4b35`) both ship
`config/app/server/prod.yaml`, which the Deployer example `deploy/files/.env.local.prod` selects. It defines the
same parameters as `dev.yaml`, with the location IDs of the demo content, but takes the values that differ per site
from the environment, with harmless defaults, and sets no demo tracking code:

| Variable | Parameter | Default in `prod.yaml` |
|---|---|---|
| `SITE_DOMAIN` | `ngsite.fh_group.site_domain`, `ngsite.bold_group.site_domain` | `localhost` |
| `COLLECTED_INFO_SENDER` | `collected_info_sender` | `noreply@localhost` |
| `COLLECTED_INFO_RECIPIENT` | `collected_info_recipient` | `webmaster@localhost` |
| `GOOGLE_TAG_MANAGER_CODE` | `ngsite.default.site_settings.google_tag_manager_code` | empty: no Tag Manager snippet |

Its siteaccess matching is `URIElement: 1`, as in `dev`, with a commented `Map\Host` example. Set the variables in
`.env.local` and change the location IDs when the site runs on its own content tree.

**1.2.0.x and 1.3.0.x releases** (`v1.2.0.0`, `1.3.0.5` and older) have no `prod` server file, so a production
server either keeps `dev` (whose values suit the demo data) or gets its own file. The `.env.local.dist` file
describes the intended way: copy `config/app/server/dev.yaml` and the `config/app/server/dev/` directory to a new
name such as `local` or `prod`, adjust the values, and set `SERVER_ENVIRONMENT` to that name in `.env.local`.

```bash
cp config/app/server/dev.yaml config/app/server/prod.yaml
cp -r config/app/server/dev config/app/server/prod
# edit prod.yaml so that it imports prod/app.yaml and prod/ibexa_siteaccess.yaml
echo 'SERVER_ENVIRONMENT=prod' >> .env.local
```

The 1.1.0.x `prod` server file reads `APP_DOMAIN`, `MAIL_FROM`, `MAIL_TO` and `GTM_CODE` from the environment. Up
to `v1.1.0.7` none of the four is defined in the committed `.env`, and selecting `prod` stops the container build with
an "environment variable not found" error. Since `v1.1.0.8` (commit `db8cc46ea`, 5 October 2026) `.env`
gives each a default (`localhost` for `APP_DOMAIN`, empty for the mail addresses and the Tag Manager ID, which leaves
the snippet out); either way, set real values in `.env.local`:

```dotenv
SERVER_ENVIRONMENT=prod
APP_DOMAIN=www.example.com
MAIL_FROM=web@example.com
MAIL_TO=office@example.com
GTM_CODE=
```

---

## 8.3 Environment variables and .env files

### 8.3.1 The .env cascade (1.1.0.x and later)

The committed `.env` holds defaults. Symfony then loads, each overriding the one before:

| File | Committed | Use |
|---|---|---|
| `.env` | yes | defaults for every host; no secrets |
| `.env.local` | **no** | this host's values and secrets |
| `.env.<APP_ENV>` | yes | defaults per environment (`.env.dev`, `.env.test` are shipped) |
| `.env.<APP_ENV>.local` | **no** | this host's values for one environment |

A real environment variable (set by the web server, the process manager or the shell) wins over every file. The
`.env` header suggests `composer dump-env prod` to compile the files into `.env.local.php` for production; that file,
when present, is read instead of parsing the `.env` files on every request.

`.env.local.dist` is a template for a developer machine: it sets `SERVER_ENVIRONMENT=local`, a MySQL
`DATABASE_URL`, a local `MAILER_DSN` and an `APP_SECRET`. Copy it to `.env.local` and change every value.

### 8.3.2 Variable reference

The variables of the committed `.env` on 1.3.0.x, grouped by what they configure. 1.1.0.x and 1.2.0.x have the same
set plus `MAILER_URL` and `IMAGEMAGICK_PATH`, and without `DEFAULT_URI`; 1.1.0.x also defines
`APP_DOMAIN`, `MAIL_FROM`, `MAIL_TO`, `GTM_CODE` and `TEST_DOMAIN` since `v1.1.0.8`.

| Variable | Configures | Shipped value or note |
|---|---|---|
| `APP_ENV` | Symfony environment | `dev` |
| `APP_SECRET` | `framework.secret`: CSRF tokens, signed URIs, remember-me | empty: **must be set** |
| `SERVER_ENVIRONMENT` | which `config/app/server/` file is loaded | `dev` |
| `DEFAULT_URI` (1.3.0.x) | base URL for URLs generated on the console | `http://localhost` |
| `DATABASE_URL` | the Doctrine connection, see [chapter 7](07-databases.md) | a PostgreSQL example; commented SQLite and MySQL examples |
| `DATABASE_CHARSET`, `DATABASE_COLLATION` | table options for the schema builder (`ibexa_doctrine_schema.yaml`) | `utf8mb4`, `utf8mb4_unicode_520_ci` |
| `DATABASE_VERSION` | server version hint | `mariadb-10.3.0`; read by nothing in `config/` on 1.1.0.x to 1.3.0.x: the version comes from `serverVersion` in `DATABASE_URL` |
| `SEARCH_ENGINE` | repository search engine | `legacy` (or `solr`) |
| `SOLR_DSN`, `SOLR_CORE` | Solr endpoint | `http://localhost:8983/solr`, `collection1` |
| `CACHE_POOL`, `CACHE_DSN`, `CACHE_NAMESPACE` | the repository's cache pool | `cache.tagaware.filesystem`, `localhost`, `ibexa` |
| `HTTPCACHE_PURGE_TYPE` | `local` (Symfony proxy) or `varnish` | commented; defaults to `local` |
| `HTTPCACHE_DEFAULT_TTL` | default TTL of content responses | `86400` |
| `HTTPCACHE_PURGE_SERVER`, `HTTPCACHE_VARNISH_INVALIDATE_TOKEN` | where purges go | `http://localhost:80`, empty |
| `TRUSTED_PROXIES` | proxies whose `X-Forwarded-*` headers are trusted (`framework.trusted_proxies`) | `127.0.0.1`; read since 5 October 2026 (branch heads, `v1.1.0.8`, `v1.2.0.1` and `1.3.0.6`), not read at all by the older releases ([chapter 14.6](14-security-hardening.md#146-behind-a-proxy-trusted-proxies)) |
| `APP_HTTP_CACHE` | wraps the kernel in the Symfony reverse proxy (`AppCache`) | not in `.env` (off); read by `public/index.php` since 5 October 2026 (branch heads, `v1.1.0.8`, `v1.2.0.1` and `1.3.0.6`), ignored by the older releases |
| `SESSION_HANDLER_ID`, `SESSION_SAVE_PATH` | session storage | native files under `var/sessions/<env>` |
| `MAILER_DSN` | Symfony Mailer transport | `null://null` (mail discarded) |
| `MESSENGER_TRANSPORT_DSN` | Messenger transport | `doctrine://default?auto_setup=0` |
| `LOCK_DSN` | Symfony Lock store | `flock` |
| `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY`, `JWT_PASSPHRASE` | REST/GraphQL JWT keys | key paths under `config/jwt/`, a placeholder passphrase |
| `CORS_ALLOW_ORIGIN` | NelmioCors | localhost only |
| `GOOGLE_RECAPTCHA_SITE_KEY`, `GOOGLE_RECAPTCHA_SECRET` | reCAPTCHA on forms | empty |
| `MAILER_LITE_API_KEY` | MailerLite newsletter integration | empty: disabled |
| `SENTRY_DSN` | error reporting | empty |
| `IBEXA_EDITION` | edition name shown by system information | `oss` |

Some parameters read variables that the committed `.env` does not define: `app.testing.site_domain` reads
`TEST_DOMAIN` (in `config/app/app.yaml`; defined in `.env` only on 1.1.0.x since `v1.1.0.8`), and the 1.1.0.x `prod`
server file reads the four variables named in [8.2.2](#822-the-server-environment) (defined since `v1.1.0.8`).
Symfony resolves an environment variable only when a service actually uses it, so a missing one surfaces as an error
the first time that service is built, not at install time. To list what the container reads and where each value
comes from:

```bash
php bin/console debug:container --env-vars --env=prod     # every %env()% the container uses, missing ones flagged
php bin/console debug:dotenv --env=prod                   # which .env file set each variable (Symfony 5.4 and later)
```

### 8.3.3 A production .env.local, worked example

A production server of 1.3.0.x on MySQL, with Varnish on the same machine and Solr, needs no more than this in
`.env.local` (mode `0640`, owned by the deploying user and the web server's group):

```dotenv
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=6f0c...   # openssl rand -hex 32
SERVER_ENVIRONMENT=prod
DATABASE_URL="mysql://nexus:change-me@127.0.0.1:3306/nexus?serverVersion=10.11.6-MariaDB&charset=utf8mb4"
SEARCH_ENGINE=solr
SOLR_DSN=http://127.0.0.1:8983/solr
SOLR_CORE=nexus
HTTPCACHE_PURGE_TYPE=varnish
HTTPCACHE_PURGE_SERVER=http://127.0.0.1:6081
TRUSTED_PROXIES=127.0.0.1
MAILER_DSN=smtp://mail.example.com:587
JWT_PASSPHRASE=another-random-value
```

`SERVER_ENVIRONMENT=prod` needs a `prod` server file ([8.2.2](#822-the-server-environment)). Since `v1.2.0.1` and
`1.3.0.6` it ships and reads four more variables; add them to the file above:

```dotenv
SITE_DOMAIN=www.example.com
COLLECTED_INFO_SENDER=web@example.com
COLLECTED_INFO_RECIPIENT=office@example.com
GOOGLE_TAG_MANAGER_CODE=
```

1.1.0.x ships a `prod` file that reads `APP_DOMAIN`, `MAIL_FROM`, `MAIL_TO` and `GTM_CODE` instead (8.2.2); on the
1.2.0.x and 1.3.0.x releases create the file yourself, or keep `dev`. After writing `.env.local`:

```bash
php bin/console debug:dotenv --env=prod | grep -E 'APP_ENV|SERVER_ENVIRONMENT|TRUSTED_PROXIES'
php bin/console cache:clear --env=prod
```

`debug:dotenv` shows each variable with the file that set it last; a value still coming from `.env` means a typo in
the name in `.env.local`. What can go wrong: a password with `@`, `:` or `/` in `DATABASE_URL` must be URL-encoded;
a real environment variable of the same name (set in the PHP-FPM pool, or exported in the shell) wins over
`.env.local` silently.

### 8.3.4 1.0.0.x: parameters and environment variables

On 1.0.0.x the root `.env` is **not** read by Symfony: it holds Docker Compose defaults (`COMPOSE_FILE`, image names,
the database name and user of the containers). Symfony's settings come from parameters:

- `app/config/parameters.yml.dist` is the template. It defines `env(...)` defaults (`env(SYMFONY_SECRET)`,
  `env(DATABASE_DRIVER)`, `env(DATABASE_HOST)`, `env(DATABASE_NAME)`, `env(SEARCH_ENGINE)`, `env(SOLR_DSN)` and
  others) and the plain parameters `purge_type`, `server_environment`, `imagemagick_path`,
  `ngsite.default.site_domain` and the root location IDs.
- `app/config/parameters.yml` is this host's copy and is not committed.
- `app/config/default_parameters.yml` maps every setting to an environment variable:
  `database_driver: '%env(DATABASE_DRIVER)%'`, `secret: '%env(SYMFONY_SECRET)%'`, `cache_pool: '%env(CACHE_POOL)%'`
  and so on. A value can therefore come from `parameters.yml` (as an `env(...)` default) or from the real
  environment, which wins.
- `app/config/env/generic.php` handles the settings that must be known when the container is compiled and cannot use
  `%env()%`: `HTTPCACHE_PURGE_TYPE`, `MAILER_TRANSPORT`, `LOG_TYPE`, `SESSION_HANDLER_ID`, `SESSION_SAVE_PATH`,
  `CACHE_POOL` (it loads `app/config/cache_pool/<pool>.yml` when such a file exists) and the `DFS_*` cluster
  settings. Change one of these and the container has to be rebuilt.

On the `1.0.0.x` branch the SQLite path is the parameter `database_path`, defaulting to `var/data_<environment>.db`
in `default_parameters.yml` and passed to Doctrine as `path:` in `app/config/config.yml`. No environment variable is
read for it: to use another file, set `database_path` in `parameters.yml` (commit `e1f3c69a4` corrected the comments
that said otherwise). `master` passes no `path:` to Doctrine. See [chapter 7](07-databases.md).

**The root location of the site.** `ngsite.default.locations.tree_root.id` decides where the front end's content
tree starts, and a wrong value shows as a 404 on the home page. Both branches ship 168 now:

| Branch | `parameters.yml.dist` | `default_parameters.yml` | Fits |
|---|---|---|---|
| `master` | `168` (since commit `7c6fa6f52`; `2` before, which is the Content root above the demo) | not set | the site root "JAC Example" that `ezplatform:install cjw-exponential-media` creates (`se7enxweb/cjw-exponential-media-site-data`); use `2` with `exponential-oss` or on an empty repository |
| `1.0.0.x` | `168` | `168` (since commit `8ac75c15c`; the two files used to disagree) | the site root of the database this branch ships (the starter SQL dump, `data/content.sql`, the `exponential-cjw` seed); use `2` after `ezplatform:install netgen-media`, whose location 168 is an article, or on an empty repository |

The value in `parameters.yml` wins over both. Look the root up rather than guessing:

```sql
-- the Content root (2) and the locations directly below it; a site root is usually one of these
SELECT node_id, parent_node_id, path_identification_string FROM ezcontentobject_tree
 WHERE node_id = 2 OR parent_node_id = 2 ORDER BY node_id;
```

---

## 8.4 Secrets

The values that must never reach a repository are `APP_SECRET` (`SYMFONY_SECRET` on 1.0.0.x), database credentials
inside `DATABASE_URL` (or `database_password`), `JWT_PASSPHRASE` and the JWT key files, `HTTPCACHE_VARNISH_INVALIDATE_TOKEN`,
`GOOGLE_RECAPTCHA_SECRET`, `MAILER_LITE_API_KEY`, `SENTRY_DSN` and any mailer password in `MAILER_DSN`.

There are three places to put them, in order of preference:

1. **The environment of the process** that runs PHP: the PHP-FPM pool (`env[APP_SECRET] = ...`), the web server
   (`SetEnv`), a systemd unit, or the environment Exponential Velocity is started with. Nothing is written into the
   installation directory.
2. **The Symfony secrets vault** (1.1.0.x and later; the vault does not exist in Symfony 3.4). Secrets are encrypted
   into `config/secrets/<env>/`; only the decryption key, `config/secrets/<env>/<env>.decrypt.private.php`, must stay
   out of the repository:

   ```bash
   php bin/console secrets:generate-keys --env=prod
   php bin/console secrets:set APP_SECRET --env=prod
   php bin/console secrets:set DATABASE_URL --env=prod
   php bin/console secrets:list --reveal --env=prod
   ```

   A vault secret is read through the same `%env(NAME)%` syntax, and a real environment variable or `.env.local`
   value of the same name still overrides it. None of the four lines ships a `config/secrets/` directory.
3. **`.env.local`** (or `parameters.yml` on 1.0.0.x), with file mode `0640` and owned by the deploying user and the
   web server's group. Simple and common; the file must be excluded from backups that leave the server.

Generate the application secret with a random value, for example `openssl rand -hex 32`. Changing it later invalidates
remember-me cookies and signed URLs, nothing else.

The JWT key pair used by the REST and GraphQL authentication of 1.1.0.x and later is created with
`php bin/console lexik:jwt:generate-keypair`, which writes `config/jwt/private.pem` and `public.pem` encrypted with
`JWT_PASSPHRASE`. Keep both files out of the repository.

---

## 8.5 Siteaccesses

### 8.5.1 Lists, groups and the scope order

A *siteaccess* is a named configuration context. Each request is assigned exactly one, and every setting under
`system:` can differ per siteaccess. The configuration has four parts:

```yaml
ibexa:                       # ezpublish: on 1.0.0.x and 1.1.0.x
    siteaccess:
        default_siteaccess: fh_eng     # used when no matcher matches
        list: [fh_eng, bold_eng, bold_ger, adminui]
        groups:                        # a siteaccess can be in several groups
            frontend_group: [fh_eng, bold_eng, bold_ger]
            admin_group: [adminui]
        match:                         # how a request is matched, see 8.5.3
            URIElement: 1
    system:
        default: { ... }               # every siteaccess
        frontend_group: { ... }        # every member of the group
        fh_eng: { ... }                # one siteaccess
        global: { ... }                # overrides everything
```

A setting is resolved in the order `global`, then the siteaccess itself, then its groups, then `default`; the first
scope that sets it wins. Put shared values in a group or in `default` and keep per-siteaccess blocks short.

`admin_group` is special: the platform uses membership of that group to recognise an administration siteaccess.
Every administration siteaccess must be in it, and the name must not change.

### 8.5.2 The siteaccesses each line ships

**1.3.0.x** (`config/app/packages/ibexa_siteaccess.yaml`), and the reference installation:

| Siteaccess | Groups | Design | Languages | Content root |
|---|---|---|---|---|
| `fh_eng` (default) | `frontend_group`, `fh_group` | `fh` | `eng-GB` | `ngsite.fh_group.locations.tree_root.id` |
| `bold_eng` | `frontend_group`, `bold_group` | `bold` | `eng-GB` | `ngsite.bold_group.locations.tree_root.id` |
| `bold_ger` | `frontend_group`, `bold_group` | `bold` | `ger-DE` | the same |
| `adminui` | `admin_group` | `ngadmin` | `eng-GB`, `ger-DE` | the whole tree |

The administration siteaccess name is the parameter `ngsite.admin_siteaccess_name` (`adminui`). Sessions are named
`IBX_SESSION_ID`.

**1.2.0.x** (`config/app/packages/ibexa_siteaccess.yaml`) has the same four plus two legacy ones:
`ngadminui` and `legacy_admin` in `ngadmin_group` (design `ngadminui`). `legacy_admin` is configured with
`ez_publish_legacy.system.legacy_admin.legacy_mode: true`, so it is served entirely by the legacy kernel. Sessions are
named `eZSESSID`, the name the legacy kernel uses, so a login is shared between the two kernels.

**1.1.0.x** (`config/app/packages/ezpublish_siteaccess.yaml`) has the same six siteaccesses as 1.2.0.x under the
`ezpublish:` key; `admin_group` there lists only `eng-GB`.

**1.0.0.x** spreads the definition over three files that are all imported (see [8.1.2](#812-100x-appconfig-and-parametersyml)):

| File | Siteaccesses | Groups |
|---|---|---|
| `ezplatform_siteaccess_defaults.yml` | `site`, `admin`, `legacy_admin`, `editor`, `edit`, `nga`, `alt`, `platformsite` | `site_group`, `admin_group` |
| `ezplatform_siteaccess.yml` | `de`, `en`, `ngadminui`, `admin`, `legacy_admin` | `frontend_group`, `ngadmin_group`, `admin_group`, `legacy_group` |
| `ngadminui.yml` | `nga`, `ngadminui` | `ngadminui` |

`ezplatform_siteaccess.yml` is imported last and sets `default_siteaccess: de`; its `de` and `en` siteaccesses use
the `cjw_app` design, the `ger-DE`/`eng-GB` languages in opposite priority, and an `index_page` of `/startseite`. How
the three `list` and `groups` blocks merge is decided by the kernel's configuration tree, not by reading the files;
check the effective result on the installed site with

```bash
php bin/console debug:config ezpublish siteaccess --env=prod
```

### 8.5.3 Matchers: how a request finds its siteaccess

Matchers are tried in the order they are written; the first that matches decides.

| Matcher | Example | Effect |
|---|---|---|
| `URIElement: 1` | `/bold_ger/about` | the first path element is the siteaccess name and is removed from the path |
| `Map\URI` | `{ en: en, de: de }` | a first path element mapped to a siteaccess |
| `Map\Host` | `{ www.example.com: fh_eng }` | the host name mapped to a siteaccess |
| `HostElement`, `Regex\Host`, `Compound\LogicalAnd` | | further matchers of the platform, see the references |

What each line ships:

- **1.1.0.x, 1.2.0.x, 1.3.0.x:** the matcher lives in the server environment, `config/app/server/dev/*_siteaccess.yaml`
  (and `server/prod.yaml` on 1.1.0.x), and is `URIElement: 1`. The default siteaccess `fh_eng` answers `/`, the
  others answer `/bold_eng/`, `/bold_ger/` and `/adminui/`. On 1.3.0.x the file carries a commented `Map\Host`
  example for the reference host.
- **1.0.0.x:** `Map\URI` for `de` and `en`, `Map\URI` for `nga` and `ngadminui`, and `Map\Host` lists for the
  project's demonstration host names. The comment above them explains why they matter beyond routing: the project's
  `AppCache` reads the `Map\Host` list to find the siteaccess of a host and never makes a response of an
  administration siteaccess public. On the releases up to `v2.5.0.6` and `1.0.0.10` that class read its settings from
  the wrong directory and used built-in demo host names instead, and rewrote private responses to public; the
  branches fixed both on 5 October 2026, released in `v2.5.0.7` and `1.0.0.11` ([chapter 14.7](14-security-hardening.md#147-http-cache-safety)). Replace the
  demo host names with yours either way.

### 8.5.4 Designs and the theme fallback

Each siteaccess names a `design`, and the design engine turns the design into an ordered list of themes. A template
referenced as `@ibexadesign/pagelayout.html.twig` (`@ezdesign/...` on 1.0.0.x and 1.1.0.x) is looked up in each theme
in that order, under `templates/themes/<theme>/` and in bundles. On 1.3.0.x:

| Design | Themes, in order |
|---|---|
| `fh` | `fh`, `app`, `common`, `standard` |
| `bold` | `bold`, `app`, `common`, `standard` |
| `app` | `app`, `common`, `standard` |
| `ngadmin`, `admin` | `common` |

Netgen Layouts has a design list of its own (`netgen_layouts.design_list`): every line sets `frontend_group` to the
Layouts design `app` (`cjw_app` on 1.0.0.x). Overriding templates is the subject of
[chapter 9](09-frontend-and-themes.md).

### 8.5.5 Adding a host name for production

To serve a site on its own host name instead of a path prefix, add a `Map\Host` matcher in the server environment
file of the production server and keep `URIElement` behind it as the fallback:

```yaml
# config/app/server/prod/ibexa_siteaccess.yaml  (1.3.0.x; use ezpublish: on 1.1.0.x)
ibexa:
    siteaccess:
        match:
            Map\Host:
                www.example.com: fh_eng
                de.example.com: bold_ger
                admin.example.com: adminui
            URIElement: 1
```

Set the matching site domain parameters (`ngsite.fh_group.site_domain`, `ngsite.bold_group.site_domain`) in the same
server environment, clear the cache ([8.13](#813-applying-and-checking-a-change)) and point the web server's host
names at the installation ([chapter 6](06-serving-the-site.md)).

---

## 8.6 Repositories, search engine and cache pool

The repository is configured once, under `repositories.default`, in `config/packages/ibexa.yaml` (1.2.0.x, 1.3.0.x),
`config/packages/ezpublish.yaml` (1.1.0.x) or `app/config/ezplatform.yml` (1.0.0.x):

```yaml
ibexa:
    repositories:
        default:
            storage: ~                      # the default Doctrine connection
            search:
                engine: '%search_engine%'   # from SEARCH_ENGINE: legacy or solr
                connection: default
```

The project adds field groups (the tabs of the content edit form) in `config/app/packages/ibexa.yaml`: on 1.3.0.x
`content`, `meta`, `extras`, `link1`, `link2` and `item_1` to `item_8`; on 1.0.0.x `content`, `meta`, `extras`,
`timing`, `event`, `design`, `links` and `input`. Their labels come from the `ezplatform_fields_groups` translation
domain in `translations/`.

`SEARCH_ENGINE=legacy` searches the database and needs nothing else; `solr` needs `SOLR_DSN` and `SOLR_CORE` and a
Solr server with the platform's schema, configured in `config/packages/ibexa_solr.yaml` (`ez_search_engine_solr:` on
1.0.0.x and 1.1.0.x). After switching the engine, the search index has to be built; that, and the HTTP cache purge
settings in the same file, are covered in [chapter 10](10-operations.md).

`system.default.cache_service_name: '%cache_pool%'` selects the cache pool from `CACHE_POOL`. The pools that can be
chosen are defined in `config/packages/cache_pool/` (`app/config/cache_pool/` on 1.0.0.x): `cache.tagaware.filesystem`
(the default), `cache.redis` and `cache.memcached`, the last two using `CACHE_DSN`. 1.0.0.x also imports
`cache_pool/cache.apcu.yml` and configures APCu for Doctrine's metadata, query and result caches in
`app/config/config.yml`. `var_dir: var/site` sets the directory for binary files (`var/site/storage/`); change it only
before any content is added.

---

## 8.7 Languages and translations

**Content languages** are listed per siteaccess, in priority order, under `languages:`. The first is the language a
siteaccess shows and creates content in; the following ones are fallbacks for content not translated into the first.
On 1.3.0.x the front siteaccesses each have one language and `adminui` has `eng-GB` and `ger-DE`. The comment in
1.0.0.x's `ezplatform_siteaccess_defaults.yml` applies to every line: the demo content and content types are in
`eng-GB`, so removing `eng-GB` from an administration siteaccess's list hides content until it is translated.

A language has to exist in the repository (Admin, Languages) before a siteaccess can use it.

**Translation siteaccesses.** `translation_siteaccesses` lists, per group, the siteaccesses offered by the language
switcher: `fh_group` offers `fh_eng`, `bold_group` offers `bold_eng` and `bold_ger`.

**Interface translations** (the words of templates, forms and messages) are Symfony translation files in
`translations/`: on 1.3.0.x `messages.en.yaml`, `messages.de.yaml`, the ICU variants `messages+intl-icu.*.yaml`,
`ngsite.en.yaml`, `nglayouts.en.yaml`, the field group labels and `ibexa_welcome_page.en.xlf`. The fallback locale
is `framework.translator.fallback: '%locale_fallback%'`, with `locale_fallback: en`. 1.0.0.x sets the default
`locale` parameter to `de` in `app/config/config.yml` and registers the Lexik and Prime translation bundles, which
keep translations in the database and make them editable in the administration;
[doc/netgen/TRANSLATIONS.md](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/doc/netgen/TRANSLATIONS.md) describes that setup.

---

## 8.8 Netgen Site API

The Netgen Site API is the layer the front end templates use to read content: it adds lazy-loaded relations, named
objects and query types, and its own content view (`ng_content_view`) next to the platform's `content_view`.

On 1.1.0.x and later its settings live in the content view prepend, `config/app/prepends/ibexa/content_view.yaml`,
under `system.frontend_group.ng_site_api`:

| Setting | Shipped | Meaning |
|---|---|---|
| `site_api_is_primary_content_view` | `true` | `ng_content_view` rules are tried first for front siteaccesses |
| `fallback_to_secondary_content_view` | `false` | do not fall back to the platform's `content_view` when no rule matches |
| `fail_on_missing_field` | `%kernel.debug%` | accessing a missing field throws in debug, returns an empty field otherwise |
| `render_missing_field_info` | `false` | do not print a notice for a missing field |
| `named_objects`, `named_queries` | `[]` (extended by the site bundle) | content, locations and queries addressable by name in templates |
| `cross_siteaccess_content.excluded_siteaccess_groups` (1.2.0.x, 1.3.0.x) | `[admin_group]` | links to content in another site never point into the administration |

The site bundle prepends its own `ng_site_api.named_objects` for `system.default`: the locations `homepage` (the
siteaccess's tree root), `site_info` and `showcase` (from the `ngsite` location parameters) and the content
`current_user`. Further settings the Site API accepts (`fallback_without_subrequest`,
`richtext_embed_without_subrequest`, `use_always_available_fallback`, `show_hidden_items`,
`enable_internal_view_route`, `redirect_internal_view_route_to_url_alias`) are left at their defaults.

On 1.0.0.x the Site API bundle is `NetgenEzPlatformSiteApiBundle` and the project sets
`netgen_ez_platform_site_api.system.frontend_group.override_url_alias_view_action: true` in
`src/AppBundle/Resources/config/ezplatform.yml`, so URL aliases are rendered by the Site API's controller.

The Layouts integration is switched to the Site API's filter-based search on every line:

```yaml
netgen_layouts_ibexa_site_api:            # netgen_layouts_ez_platform_site_api: on 1.0.0.x and 1.1.0.x
    search_service_adapter: filter
```

(`config/packages/netgen_layouts.yaml`, or `app/config/config.yml` on 1.0.0.x.) The filter service queries the
database directly, so Layouts and the Content Browser find content even when `SEARCH_ENGINE=solr` and the index is
not yet built.

---

## 8.9 Netgen Layouts and the Content Browser

Layouts' own configuration is mostly data (layouts, zones, blocks and mapping rules live in the `nglayouts_*`
database tables and are edited in the Layouts administration, see
[chapter 5](05-the-demo-site-and-layouts.md)). What lives in files is:

| What | 1.1.0.x and later | 1.0.0.x |
|---|---|---|
| Block types, block type groups, block definitions | `config/app/prepends/netgen_layouts/blocks.yaml` | `src/AppBundle/Resources/config/layouts/blocks.yml`, `block_definitions.yml` |
| Block view templates per definition and view type | `prepends/netgen_layouts/block_view.yaml` | `layouts/block_view.yml` |
| Item view templates (items in lists and galleries) | `prepends/netgen_layouts/item_view.yaml` | `layouts/item_view.yml` |
| Component blocks | `prepends/netgen_layouts/components.yaml` | not present |
| Layouts design per siteaccess | `netgen_layouts.design_list` and `system.frontend_group.design` in the siteaccess file | the same, in `ezplatform_siteaccess.yml` |
| Content Browser templates | `config/app/packages/content_browser.yaml` | `src/AppBundle/Resources/config/browser.yml` |

On 1.3.0.x `blocks.yaml` defines the block types `list_zigzag` and `list_accordion`, the group `listing`, and
overrides of the `title`, `list` and `gallery` definitions. `content_browser.yaml` points the Content Browser's item
and preview templates at `@ibexadesign/browser/item.html.twig` and `@ibexadesign/browser/ngcb_preview.html.twig`.

Doctrine is told to leave the Layouts tables alone when it compares schemas:
`doctrine.dbal.schema_filter: ~^(?!nglayouts_)~` in `config/packages/doctrine.yaml` (1.1.0.x and later). Layouts manages its tables with
its own migrations.

---

## 8.10 Site bundle parameters (ngsite)

The site bundle (`se7enxweb/site-bundle`, a fork of the Netgen site bundle) reads its settings as siteaccess-aware
parameters named `ngsite.<scope>.<setting>`, where scope is `default`, a group or a siteaccess. The ones an operator
changes are in the server environment file, because they hold location IDs of the installed content:

| Parameter (1.3.0.x `server/dev/app.yaml`) | Meaning |
|---|---|
| `ngsite.fh_group.site_domain`, `ngsite.bold_group.site_domain` | host name of each site, used in absolute links and mails |
| `ngsite.<group>.locations.tree_root.id` | root location of each site; also its `content.tree_root.location_id` |
| `ngsite.<group>.locations.site_info.id` | the Site info object (logo, footer, social links, settings) |
| `ngsite.<group>.locations.showcase.id` | the showcase location |
| `ngsite.default.locations.ng_component_*.id` | locations of the component blocks (hero, quote, about, features, logos, lead) |
| `collected_info_sender`, `collected_info_recipient` | sender and recipient of form submissions |
| `ngsite.default.site_settings.google_tag_manager_code` | Google Tag Manager container; the shipped value is a demonstration code to be replaced before going live |

`config/app/app.yaml` adds `ngsite.default.search.content_types` (the content types the site search covers) and
`ngsite.default.lazy_loading.enabled`. `config/app/packages/templates.yaml` maps the site bundle's user pages and mails
(activation, password reset) to templates of the design. On 1.0.0.x the same parameters live in
`src/AppBundle/Resources/config/parameters.yml` and `app/config/parameters.yml`, with `ngsite.default.*` instead of
per-group scopes, and include the mail sender name and address used by the legacy kernel too.

The location IDs fit the shipped demo data only. After installing different content, or after an import that
renumbers locations, set them to the real IDs; a wrong `tree_root.id` shows as a 404 on the site's home page.

---

## 8.11 Image variations

An image variation is a named, cached rendition of an image field: the platform passes the original through a list
of filters (Liip Imagine, with ImageMagick or GD) the first time a variation is requested and stores the result under
`var/site/storage/images/_aliases/<variation>/`.

The project defines its variations in `config/app/packages/image.yaml` (1.1.0.x and later) or
`src/AppBundle/Resources/config/image.yml` (1.0.0.x), under `system.default.image_variations`:

| Line | Variations |
|---|---|
| 1.3.0.x | `i30`, `i160`, `i320`, `i480`, `i770`, `i1320`, `i1920`: scale to that width, never up, and strip metadata |
| 1.0.0.x | `i30` to `i1920` plus 16:9 crops `i770_16-9`, `i900_16-9`, `i1200_16-9`; `small`, `medium`, `large` for `ngadmin_group` |

A variation is declared like this:

```yaml
ibexa:
    system:
        default:
            image_variations:
                i770:
                    reference: original
                    filters:
                        - { name: geometry/scalewidthdownonly, params: [770] }
                        - { name: strip }

liip_imagine:
    filter_sets:
        i770:
            quality: 85
            jpeg_quality: 85
```

The `liip_imagine.filter_sets` entry of the same name sets the output quality; `i30`, the placeholder used for lazy
loading (`ngsite.default.lazy_loading.initial_image_alias` on 1.0.0.x), is deliberately saved at quality 20.

ImageMagick is configured by `imagemagick.path` (1.0.0.x, parameter `imagemagick_path`) or, for the legacy kernel's
image handling on 1.1.0.x and 1.2.0.x, by `config/app/app_legacy.yaml`, which reads `IMAGEMAGICK_PATH`
(`/usr/bin` in `.env`):

| Line | `app_legacy.yaml` loaded | Parameter names in it |
|---|---|---|
| 1.1.0.x, `v1.1.0.7` and older | no (the kernel does not import it, so the file has no effect) | `ibexa.image.imagemagick.*`, which nothing on this line reads |
| 1.1.0.x since `v1.1.0.8` (commit `e8c19aee4`, 5 October 2026) | yes | `ezpublish.image.imagemagick.enabled`, `ezpublish.image.imagemagick.executable_path`, the names the 3.3 legacy bridge reads |
| 1.2.0.x | yes | `ibexa.image.imagemagick.enabled`, `ibexa.image.imagemagick.executable_path` |

Check the result with `php bin/console debug:container --parameters | grep imagemagick`.

When a variation's filters change, the stored renditions are stale; they are removed with the commands in
[chapter 10](10-operations.md).

---

## 8.12 Legacy bridge settings

1.0.0.x, 1.1.0.x and 1.2.0.x run the Exponential Platform Legacy kernel next to the Symfony kernel through the
legacy bridge (`EzPublishLegacyBundle`) and the site legacy bundle (`NetgenSiteLegacyBundle`). The legacy kernel is
installed into `ezpublish_legacy/` (the `ezpublish-legacy-dir` setting in `composer.json`; the directory is not
committed). 1.3.0.x has no legacy kernel.

The legacy kernel keeps its own INI settings in `ezpublish_legacy/settings/`, but on these lines most of them are
*injected* from Symfony configuration so that both kernels agree on the database, siteaccesses and mail:

```yaml
netgen_site_legacy:                       # config/app/packages/legacy.yaml; src/AppBundle/Resources/config/legacy.yml on 1.0.0.x
    system:
        default:
            injected_settings:            # replace a legacy setting
                site.ini:
                    SiteSettings/SiteList: "%ezpublish.siteaccess.list%"
                    SiteAccessSettings/ForceVirtualHost: "true"
            injected_merge_settings:      # add to a legacy array setting
                site.ini:
                    ExtensionSettings/ActiveExtensions: [ ... ]
```

Legacy settings are strings: booleans are written as `"true"` and `"false"`, as the comments in the file say. On
1.0.0.x the same file also injects the active legacy extensions, mail transport settings from the `mailer_*`
parameters, cronjob parts and settings of individual legacy extensions. The siteaccess that the legacy kernel serves
alone is marked with `ez_publish_legacy.system.legacy_admin.legacy_mode: true`.

Do not put credentials for external services into `injected_settings` in a committed file. Reference a parameter
instead (`"%some_parameter%"`) and set the parameter from `.env.local` or `parameters.yml`.

Everything else about the legacy kernel's configuration (INI override order, `settings/override/`,
`settings/siteaccess/`) is the same as in a standalone installation and is described in the Exponential 6 book,
[chapter 10](https://github.com/se7enxweb/exponential/blob/main/doc/install/10-after-installing.md).

---

## 8.13 Applying and checking a change

Symfony compiles all configuration into a cached container under `var/cache/<env>/`. In `dev` with debug on it
notices changed files and rebuilds; in `prod` it does not. After any change to configuration, `.env*` files or
`parameters.yml`:

```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod      # optional, cache:clear already warms up
```

Then make the processes that serve the site load the new container:

- **Exponential Velocity** keeps the application in persistent workers. Reload the server
  (`qbixctl graceful` or `qbixconsole server:reload`; see [chapter 6](06-serving-the-site.md)) so that every worker is
  replaced, or recycle all workers from the control panel's Workers tab.
- **PHP-FPM** reads the new container on the next request; reload the pool only when you changed PHP settings or the
  pool's environment variables.
- On the lines with the legacy kernel (1.0.0.x to 1.2.0.x), also clear the legacy caches when injected settings changed:
  `php bin/console ezpublish:legacy:script bin/php/ezcache.php --clear-all`.

To see what a setting really is after all files, prepends and environment variables are merged, ask the container
instead of reading files:

```bash
php bin/console debug:config ibexa                      # ezpublish on 1.0.0.x and 1.1.0.x
php bin/console debug:config netgen_layouts
php bin/console debug:container --parameters | grep ngsite
php bin/console debug:dotenv                            # Symfony 5.4 and later: which .env file set what
php bin/console debug:container --env-vars              # environment variables the container uses
```

`debug:config` shows the configuration of an extension, not the per-siteaccess result. To check a siteaccess
setting, request a page of that siteaccess with the Symfony profiler enabled in `dev`, or inspect the parameter
siteaccess-scoped parameters (named `ibexa.site_access.config.<scope>.<setting>` on 1.2.0.x and 1.3.0.x and
`ezsettings.<scope>.<setting>` on the earlier lines) with `debug:container --parameters`.

---

## 8.14 The reference installation

The live reference installation of the 1.3.0.x line runs with:

| Setting | Value |
|---|---|
| `APP_ENV` | `dev` |
| `SERVER_ENVIRONMENT` | `dev` (the only server file present) |
| Siteaccesses and matcher | as shipped: `fh_eng` (default), `bold_eng`, `bold_ger`, `adminui`, `URIElement: 1` |
| Database | SQLite, `var/data_dev.db` through `DATABASE_URL` ([chapter 7](07-databases.md)) |
| `SEARCH_ENGINE` | `legacy` |
| `CACHE_POOL` | `cache.tagaware.filesystem` |
| `MESSENGER_TRANSPORT_DSN` | `doctrine://default?auto_setup=0` |
| Secrets vault | none; no `config/secrets/` and no `config/jwt/` key files |

Its configuration matches the 1.3.0.x branch as it was before 5 October 2026, file for file (it predates that
day's fixes: its `config/packages/framework.yaml` sets no `trusted_proxies` and its `public/index.php` does not read
`APP_HTTP_CACHE`), with these local differences:

- `config/bundles.php` and `config/packages/nova_ezseo.yaml` add the Novactive SEO bundle (with its `llms.txt`
  feature) and `config/routes/novaezseo.yaml` its routes.
- `config/packages/csrf.yaml` uses session-based CSRF tokens (`csrf_protection: true`) instead of the stateless
  tokens of the branch, because the administration login form and tree views behind the site's proxy were rejected
  with stateless tokens.
- `config/packages/lexik_jwt_authentication.yaml` signs tokens with `APP_SECRET` (HS256) and disables all token
  extractors, instead of the branch's key-pair configuration.
- `config/app/services.yaml` does not carry the branch's public `ezpublish.config.resolver` alias.

The `.env` of the reference installation repeats the Ibexa block twice; with duplicate keys the last value wins, so
keep one copy when you base a `.env.local` on it. Running a public site in `APP_ENV=dev` exposes the profiler and
detailed errors; a production site sets `APP_ENV=prod`.

---

## 8.15 Checklist

- [ ] The correct root key is used for the line (`ezpublish:` on 1.0.0.x and 1.1.0.x, `ibexa:` on 1.2.0.x and later).
- [ ] `APP_ENV=prod` (`SYMFONY_ENV=prod` on 1.0.0.x, also for the console) on production.
- [ ] `APP_SECRET` (`SYMFONY_SECRET`) is a random value set outside committed files.
- [ ] Database credentials, `JWT_PASSPHRASE` and API keys are in the environment, the vault or `.env.local`, never in `.env`.
- [ ] A server environment exists for this server, `SERVER_ENVIRONMENT` names it, and every variable it reads is defined.
- [ ] `ngsite.*.locations.*.id` match the installed content.
- [ ] Every administration siteaccess is in `admin_group`.
- [ ] Production host names are in a `Map\Host` matcher before `URIElement` (on 1.0.0.x: every administration host is in the map).
- [ ] `languages` of the administration siteaccess include every language content is translated into.
- [ ] `SEARCH_ENGINE`, `CACHE_POOL` and the purge settings are what this server provides.
- [ ] `TRUSTED_PROXIES` lists exactly the proxies in front of PHP, and your `framework` configuration reads it
      (since `v1.1.0.8`, `v1.2.0.1` and `1.3.0.6` it does; older releases need the block of [chapter 14.6](14-security-hardening.md#146-behind-a-proxy-trusted-proxies)).
- [ ] `APP_HTTP_CACHE` is on only when no Varnish is in front (1.1.0.x and later); `SYMFONY_HTTP_CACHE=0` with Varnish
      on 1.0.0.x.
- [ ] On the legacy lines, no credentials are in committed `injected_settings`.
- [ ] `cache:clear --env=prod` has been run and Velocity reloaded (or PHP-FPM serving the new container).
- [ ] `debug:config` shows the values you expect.

---

## 8.16 References

In this repository (`master`):

- [doc/netgen/TRANSLATIONS.md](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/doc/netgen/TRANSLATIONS.md): editable translations in the administration (1.0.0.x)
- [doc/INSTALL.md](../INSTALL.md): the short installation guide; [doc/netgen/INSTALL.md](../netgen/INSTALL.md): the upstream guide, corrected for this repository
- 1.0.0.x configuration: [app/config/config.yml](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/app/config/config.yml),
  [app/config/ezplatform.yml](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/app/config/ezplatform.yml),
  [app/config/ezplatform_siteaccess.yml](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/app/config/ezplatform_siteaccess.yml),
  [app/config/ezplatform_siteaccess_defaults.yml](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/app/config/ezplatform_siteaccess_defaults.yml),
  [app/config/parameters.yml.dist](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/app/config/parameters.yml.dist),
  [app/config/default_parameters.yml](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/app/config/default_parameters.yml),
  [app/config/env/generic.php](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/app/config/env/generic.php),
  [src/AppBundle/Resources/config/](https://github.com/se7enxweb/exponential-platform-nexus/tree/master/src/AppBundle/Resources/config), [web/app.php](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/web/app.php)

On the other line branches:

- 1.3.0.x: [.env](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/.env),
  [.env.local.dist](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/.env.local.dist),
  [src/Kernel.php](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/src/Kernel.php),
  [config/packages/ibexa.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/packages/ibexa.yaml),
  [config/app/packages/ibexa_siteaccess.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/app/packages/ibexa_siteaccess.yaml),
  [config/app/packages/image.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/app/packages/image.yaml),
  [config/app/server/dev/app.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/app/server/dev/app.yaml),
  [config/app/prepends/ibexa/content_view.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/app/prepends/ibexa/content_view.yaml),
  [config/app/prepends/netgen_layouts/blocks.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/app/prepends/netgen_layouts/blocks.yaml)
- 1.2.0.x: [config/app/packages/ibexa_siteaccess.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.2.0.x/config/app/packages/ibexa_siteaccess.yaml),
  [config/app/packages/legacy.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.2.0.x/config/app/packages/legacy.yaml)
- 1.1.0.x: [config/app/app_legacy.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.1.0.x/config/app/app_legacy.yaml),
  [config/packages/ezpublish.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.1.0.x/config/packages/ezpublish.yaml),
  [config/app/packages/ezpublish_siteaccess.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.1.0.x/config/app/packages/ezpublish_siteaccess.yaml),
  [config/app/server/prod.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.1.0.x/config/app/server/prod.yaml),
  [src/DependencyInjection/AppExtension.php](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.1.0.x/src/DependencyInjection/AppExtension.php)

Exponential:

- The Exponential 6 book: <https://github.com/se7enxweb/exponential/blob/main/doc/install/README.md>, in particular
  [chapter 10, After installing](https://github.com/se7enxweb/exponential/blob/main/doc/install/10-after-installing.md)
  for the legacy kernel's INI settings
- Exponential Velocity: <https://github.com/se7enxweb/exponential-velocity> (`docs/workers.md` for reloading workers)
- The site bundle fork: <https://github.com/se7enxweb/site-bundle>

Symfony:

- [Configuring Symfony](https://symfony.com/doc/current/configuration.html),
  [environments](https://symfony.com/doc/current/configuration.html#configuration-environments),
  [.env files](https://symfony.com/doc/current/configuration.html#configuring-environment-variables-in-env-files),
  [secrets](https://symfony.com/doc/current/configuration/secrets.html),
  [environment variable processors](https://symfony.com/doc/current/configuration/env_var_processors.html),
  [Symfony 3.x configuration](https://symfony.com/doc/3.x/configuration.html) for 1.0.0.x
- [Runtime component](https://symfony.com/doc/current/components/runtime.html),
  [Dotenv component](https://github.com/symfony/dotenv)

Upstream platform documentation (for the configuration keys underneath):

- SiteAccess: [current](https://doc.ibexa.co/en/latest/multisite/siteaccess/siteaccess/),
  [matchers](https://doc.ibexa.co/en/latest/multisite/siteaccess/siteaccess_matching/),
  [2.5](https://doc.ibexa.co/en/2.5/guide/siteaccess/)
- [Image variations](https://doc.ibexa.co/en/latest/content_management/images/images/#image-variations),
  [languages](https://doc.ibexa.co/en/latest/multisite/languages/languages/),
  [design engine](https://doc.ibexa.co/en/latest/templating/design_engine/design_engine/)
- Netgen Site API: <https://docs.netgen.io/projects/site-api/en/latest/>
- Netgen Layouts: <https://docs.netgen.io/projects/layouts/en/latest/>
- Liip Imagine: <https://symfony.com/bundles/LiipImagineBundle/current/index.html>

[Previous: 7. Databases](07-databases.md) · [Next: 9. Front end and themes](09-frontend-and-themes.md) ·
[Contents](README.md)
